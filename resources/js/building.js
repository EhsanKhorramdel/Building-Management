import './ui';
import { config } from './building/api';
import { navigation } from './building/navigation';
import { announcements } from './building/announcements';
import { chat } from './building/chat';
import { payments } from './building/payments';
import { polls } from './building/poll';
import { uploader } from './building/uploader';

announcements();
chat();
payments();
polls();
uploader();
navigation(config.initialPanel);
