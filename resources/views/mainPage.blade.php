<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ __('direction') }}">

<head>
    <meta charset="UTF-8" />
    <title>صفحه اصلی - مدیریت هوشمند ساختمان</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/favIcon/apartment.ico') }}" />
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/mainPage.css') }}" />
</head>

<body>
    <div class="c-buttons">
        <button class="c-buttons__button c-buttons__button--register">
            <a href="{{ route('register') }}">ثبت نام</a>
        </button>
        <button class="c-buttons__button c-buttons__button--login">
            <a href="{{ route('login') }}">ورود</a>
        </button>
    </div>
    <section class="c-section">
        <h2 class="c-section__title"><span></span></h2>

        <ul class="c-services">
            <li class="c-services__item">
                <h3>مدیریت آسان تر</h3>
                <p>
                    با پیوست ساختمان خود، به راحتی در پنل کاربری خود بهشون دسترسی پبدا کن
                </p>
            </li>
            <li class="c-services__item">
                <h3>نظرسنجی</h3>
                <p>برای مدیریت بهتر ساختمان، نطر همه ی افراد مهمه،
                    به راحتی نطرسنجی بسازید و نتیجه رو مشاهده کنید
                </p>
            </li>
            <li class="c-services__item">
                <h3>تابلوی اعلانات</h3>
                <p>در هر لحطه و هر جا که باشی به راحتی از تابلوی اعلانات ساختمان باخبر شو
                </p>
            </li>
            <li class="c-services__item">
                <h3>پرداخت هزینه و شارژ</h3>
                <p>هزینه های ساختمان رو ببین و سریع پرداخت کن
                </p>
            </li>
            <li class="c-services__item">
                <h3>گروه چت</h3>
                <p>
                    هر ساختمان یه گروه داره
                    به راحتی با ساکنین در ارتباط باش و در رابطه با اتفاقات ساختمان گفتگو کن
                </p>
            </li>
            <li class="c-services__item">
                <h3>مدیریت هوشمند ساختمان</h3>
                <p>با دسترسی به پنل ساختمان خود، مدیریت ساختمان خود را به آسانی پیش ببرید</p>
            </li>
        </ul>
    </section>
</body>

</html>
