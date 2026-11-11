<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AvatarController extends Controller
{
    public function show(Request $request, User $user)
    {
        $viewer = $request->user();
        abort_unless($viewer->is($user) || $viewer->isGlobalAdmin() || $viewer->userRoles()
            ->where('active', true)->whereIn('role', ['manager', 'resident'])
            ->whereIn('complex_id', $user->userRoles()->select('complex_id')->where('active', true)->whereIn('role', ['manager', 'resident']))
            ->exists(), 403);

        $disk = Storage::disk('local');
        abort_unless($user->avatar_path && $disk->exists($user->avatar_path), 404);

        return $disk->response($user->avatar_path, 'avatar.png', ['Content-Type' => 'image/png', 'Cache-Control' => 'private, no-store']);
    }

    public function update(Request $request)
    {
        $file = $request->validate(['avatar' => ['required', 'file', 'mimes:gif,jpg,webp,png', 'max:5120', 'dimensions:max_width=4096,max_height=4096']])['avatar'];
        $image = @imagecreatefromstring($file->get());
        if ($image === false) {
            throw ValidationException::withMessages(['avatar' => 'تصویر انتخاب‌شده قابل خواندن نیست.']);
        }

        $side = min(imagesx($image), imagesy($image));
        $thumbnail = imagecreatetruecolor(192, 192);
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        imagecopyresampled($thumbnail, $image, 0, 0, intdiv(imagesx($image) - $side, 2), intdiv(imagesy($image) - $side, 2), 192, 192, $side, $side);
        ob_start();
        imagepng($thumbnail);
        $bytes = ob_get_clean();

        $disk = Storage::disk('local');
        $path = 'avatars/'.Str::uuid().'.png';
        abort_unless($disk->put($path, $bytes), 500);

        try {
            $this->replace($request->user(), $path);
        } catch (\Throwable $failure) {
            $disk->delete($path);

            throw $failure;
        }

        return back()->with(['page' => 'setting', 'status' => 'success', 'message' => 'تصویر پروفایل ذخیره شد.']);
    }

    public function destroy(Request $request)
    {
        $this->replace($request->user(), null);

        return back()->with(['page' => 'setting', 'status' => 'success', 'message' => 'تصویر پروفایل حذف شد.']);
    }

    private function replace(User $user, ?string $path): void
    {
        $previous = DB::transaction(function () use ($user, $path) {
            $locked = User::lockForUpdate()->findOrFail($user->id);
            $previous = $locked->avatar_path;
            $locked->avatar_path = $path;
            $locked->save();

            return $previous;
        });
        if ($previous) {
            rescue(fn () => Storage::disk('local')->delete($previous));
        }
    }
}
