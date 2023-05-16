import { FtpOptions } from './ftpOptions.interface';

export interface AdvancedOptions {
    active: boolean;
    wordpress: boolean;
    brizy: boolean;
    brizyPro: boolean;
    ftp: FtpOptions;
}
