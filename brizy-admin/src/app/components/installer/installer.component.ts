import { Component, Input, OnInit } from '@angular/core';
import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { TranslateService } from '@ngstack/translate';
import { AdvancedOptions } from 'src/app/interfaces/advancedOptions';
import { InitData } from 'src/app/interfaces/initData.interface';
import { InstallerService } from 'src/app/services/installer.service';
import { ConfirmModal } from 'src/app/components/confirmModal/confrimModal.component';

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
    initDataFailed = false;

    defaultConfirmModalSettings = { backdrop: false, keyboard: false, centered: true, modalDialogClass: 'panel panel-primary', windowClass: 'modal whmcs-modal fade in show'};

    constructor(
        private installerService: InstallerService,
        private translate: TranslateService,
        private modalService: NgbModal
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
        this.loadingData = true;
        this.installerService.serviceInfo(this.serviceId).subscribe({
            next: (response) => {
                this.initData.installed = response.data.installed;

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

                this.installationModal = true;
                this.loadingData = false;
            },
            error: () => {
                this.loadingData = false

            },
        });


    }

    install() {
        const confrimModal = this.modalService.open(ConfirmModal, this.defaultConfirmModalSettings);
        confrimModal.componentInstance.confirm = this.translate.get('installer.confirmInstallation');

        confrimModal.result.then((result) => {
            if (result) {
                this.log = [];
                this.installationStatus = 1;
                const steps = [
                    {
                        'description':  this.translate.get('installer.installationSteps.databaseCheck'),
                        'method': this.createDatabase(),
                    },
                    {
                        'description': this.translate.get('installer.installationSteps.userPrivilages'),
                        'method': this.createUser()
                    },
                    {
                        'description': this.translate.get('installer.installationSteps.uploadingScript'),
                        'method': this.uploadingInstallationScript()
                    },
                    {
                        'description': this.translate.get('installer.installationSteps.runningScirpt'),
                        'method': this.runRemoteScript()
                    },

                ];

                const stepsFtp = [
                    {
                        'description': this.translate.get('installer.installationSteps.ftpConnectionTest'),
                        'method': this.testFtpConnection()
                    },
                    {
                        'description': this.translate.get('installer.installationSteps.uploadingScript'),
                        'method': this.uploadingInstallationScriptFtp()
                    },
                    {
                        'description': this.translate.get('installer.installationSteps.runningScirpt'),
                        'method': this.runRemoteScriptFtp()
                    },

                ];

                if (this.advanced.ftp.active) {
                    this.executeSteps(stepsFtp);
                } else {
                    this.executeSteps(steps);
                }
            }

        }, (dismissReason) => {

        });
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
        this.loadingData = true;
        this.installerService.initData(this.serviceId).subscribe({
            next: (response) => {
                this.initData = response.data;
                this.loadingData = false;
            },
            error: () => {
                this.loadingData = false;
                this.initDataFailed = true;
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
