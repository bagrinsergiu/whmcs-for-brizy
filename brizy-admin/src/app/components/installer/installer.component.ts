import { Component, Input, OnInit } from '@angular/core';
import { TranslateService } from '@ngstack/translate';
import { AdvancedOptions } from 'src/app/interfaces/advancedOptions';
import { InitData } from 'src/app/interfaces/initData.interface';
import { InstallerService } from 'src/app/services/installer.service';

@Component({
    selector: 'app-brizy-installer',
    templateUrl: './installer.component.html',
    styleUrls: ['./installer.component.scss']
})
export class InstallerComponent implements OnInit {

    @Input() pro;
    @Input() serviceId;
    @Input() lang: string = 'english';

    installationStatus = 0;
    installationModal = false;
    log = [];

    advanced: AdvancedOptions = {
        'active': false,
        'wordpress': true,
        'brizy': true,
        'brizyPro': true,
        'ftp': {
            'active': false,
            'host': '',
            'port': 21,
            'path': '',
            'username': '',
            'password': '',
            'dbName': '',
            'dbUser': '',
            'dbPassword': '',
        }
    };

    loadingData = false;
    initData: InitData;



    constructor(
        private installerService: InstallerService,
        private translate: TranslateService
    ) {
        this.installationModal = false;
        this.translate.use(this.lang);
    }

    ngOnInit() {
        this.getInitData();

        if (!this.pro) {
            this.advanced.brizyPro = false;
        } else {
            this.advanced.brizyPro = true;
        }
    }

    closeInstallationModal() {
        this.installationModal = false;
    }

    openInstallationModal() {
        this.installationModal = true;
    }

    install() {
        this.log = [];
        this.installationStatus = 1;
        const steps = [
            {
                'description': 'Checking database',
                'method': this.createDatabase(),
            },
            {
                'description': 'Checking user and privileges',
                'method': this.createUser()
            },
            {
                'description': 'Uploading the installation script',
                'method': this.uploadingInstallationScript()
            },
            {
                'description': 'Running script',
                'method': this.runRemoteScript()
            },

        ];

        const stepsFtp = [
            {
                'description': 'Testing FTP connection',
                'method': this.testFtpConnection()
            },
            {
                'description': 'Uploading the installation script',
                'method': this.uploadingInstallationScriptFtp()
            },
            {
                'description': 'Running script',
                'method': this.runRemoteScriptFtp()
            },

        ];

        if (this.advanced.ftp.active) {
            this.executeSteps(stepsFtp);
        } else {
            this.executeSteps(steps);
        }

    }


    executeSteps(steps: Array<any>) {

        if (steps.length > 0) {
            const step = steps.shift();
            this.log.push(step);
            const stepResponse = step.method.subscribe({
                next: () => {
                    this.executeSteps(steps);
                    step.response = ' OK';
                },
                error: (response) => {

                    if (response.error?.data?.error?.message) {
                        step.response = ' ' + response.error?.data?.error?.message;
                    } else {
                        step.response = ' Critical failure...';
                    }

                    this.advanced.active = true;
                    this.advanced.ftp.active = true;
                    this.installationStatus = 2;
                },
            });
        } else {

            this.installationStatus = 3;
        }
    }


    getInitData() {
        this.installerService.initData(this.serviceId).subscribe({
            next: (response) => {
                this.initData = response.data;

                this.advanced.ftp.host = String(response.data.host);

                if (this.initData.installed?.brizyPro) {
                    this.advanced.brizyPro = false;
                    this.advanced.active = true;
                }

                if (this.initData.installed?.brizy) {
                    this.advanced.brizy = false;
                    this.advanced.active = true;
                }

                if (this.initData.installed?.wordpress) {
                    this.advanced.wordpress = false;
                    this.advanced.active = true;
                }
            },
            error: () => {


            },
        });


    }

    createDatabase() {
        return this.installerService.creteDb(this.serviceId);
    }

    createUser() {
        return this.installerService.creteDbUser(this.serviceId);
    }

    uploadingInstallationScript() {
        return this.installerService.uploadInstallationScript(this.serviceId, this.advanced);
    }

    runRemoteScript() {
        return this.installerService.addCronJob(this.serviceId);
    }

    testFtpConnection() {
        return this.installerService.testFtpConnection(this.serviceId, this.advanced);
    }

    uploadingInstallationScriptFtp() {
        return this.installerService.uploadInstallationScriptFtp(this.serviceId, this.advanced);
    }

    runRemoteScriptFtp() {
        return this.installerService.runInstallationScriptFtp(this.serviceId);
    }


}
