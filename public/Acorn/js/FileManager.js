class FileManager {
    constructor(uploadSelector, importFileSelector, previewFileSelector, checkImportRoute, token, import_type, abortImportRoute, longRunningMinutes) {
        this.$fileUpload = $(uploadSelector);
        this.$uploadList = $("#file-upload-list");
        this.$importFileSelect = $(importFileSelector);
        this.$previewFile = $(previewFileSelector);
        this.checkImportRoute = checkImportRoute;
        this.token = token;
        this.abortImportRoute = abortImportRoute || null;
        this.longRunningMinutes = (longRunningMinutes !== undefined && longRunningMinutes !== null) ? parseInt(longRunningMinutes, 10) : 30;
        this.resumable = null;
        this.replaceRecords = false;
        this.replaceDate = $("#replace_date");
        this.import_type = import_type;
        this._uploadErrorShown = false;
        this.init();
    }

    init() {
        this.initResumable();
        this.bindEvents();
        this.bindAbortImport();
        FileManager.checkImportProcess(this.checkImportRoute, this.import_type, this.abortImportRoute, this.token, this.longRunningMinutes);
    }

    bindAbortImport() {
        const self = this;
        $(document).off('click.filemanager-abort', '.js-abort-import-btn').on('click.filemanager-abort', '.js-abort-import-btn', function () {
            const $btn = $(this);
            const $container = $("#importProcessing");
            const importId = $btn.data('import-id');
            const abortRoute = $container.data('abort-route');
            const token = $container.data('abort-token');
            if (!abortRoute || !importId || !token) return;
            $btn.prop('disabled', true).text('Aborting...');
            $.ajax(abortRoute, {
                type: 'POST',
                data: { import_id: importId, _token: token }
            }).done(function () {
                $container.html('<h4 class="mb-5 alert alert-info">Import aborted. No further records will be processed.</h4>');
                FileManager._wasProcessing = false;
                $("#importFile").prop("disabled", false);
                setTimeout(function () { $container.html(''); }, 10000);
            }).fail(function () {
                $btn.prop('disabled', false).text('Abort import');
                if (typeof $.notify !== 'undefined') {
                    $.notify({ title: 'Error', message: 'Failed to abort import.', icon: 'cs-error-hexagon' }, { type: 'danger', z_index: 2000 });
                } else {
                    alert('Failed to abort import.');
                }
            });
        });
    }

    initResumable() {
        if (this.$fileUpload.length > 0) {
            this.resumable = new Resumable({
                chunkSize: 1 * 1024 * 1024, // 1MB
                simultaneousUploads: 1,
                testChunks: false,
                throttleProgressCallbacks: 1,
                maxChunkRetries: 0,
                target: this.$fileUpload.data('url'),
                query: { _token: this.token, import_type: this.import_type || $(this.$importFileSelect).data("import_type") }
            });

            if (!this.resumable.support) {
                $('#resumable-error').show();
            } else {
                this.bindResumableEvents();
            }
        }
    }

    bindResumableEvents() {
        this.resumable.on('fileAdded', (file) => this.handleFileAdded(file));
        this.resumable.on('fileSuccess', (file, message) => this.handleFileSuccess(file, message));
        this.resumable.on('fileError', (file, message) => this.handleFileError(file, message));
        this.resumable.on('fileProgress', (file) => this.handleFileProgress(file));
    }

    parseUploadErrorResponse(message) {
        if (!message) return null;
        let str = null;
        if (typeof message === 'string') str = message;
        else if (message.responseText) str = message.responseText;
        else if (message.response && typeof message.response === 'string') str = message.response;
        else if (message.error !== undefined) return this._errorToString(message.error);
        else if (message.message) return typeof message.message === 'string' ? message.message : this._errorToString(message.message);
        if (!str) return null;
        try {
            const data = JSON.parse(str);
            const err = data && (data.error !== undefined ? data.error : data.message);
            return err !== undefined && err !== null ? this._errorToString(err) : null;
        } catch (e) {
            return str.length < 200 ? str : 'File could not be uploaded.';
        }
    }

    _errorToString(err) {
        if (err == null) return null;
        if (typeof err === 'string') return err;
        if (Array.isArray(err)) return err.map(function (e) { return typeof e === 'string' ? e : String(e); }).join(' ');
        if (typeof err === 'object') {
            const parts = [];
            for (const key in err) if (Object.prototype.hasOwnProperty.call(err, key)) {
                const v = err[key];
                if (Array.isArray(v)) parts.push(v.join(' '));
                else if (typeof v === 'string') parts.push(v);
                else parts.push(String(v));
            }
            return parts.length ? parts.join(' ') : 'File could not be uploaded.';
        }
        return String(err);
    }

    showUploadErrorToast(msg) {
        const m = msg || 'File format does not match the sample file format.';
        setTimeout(() => {
            if (typeof $ !== 'undefined' && $.notify) {
                $.notify({ title: 'Upload Error', message: m, icon: 'cs-error-hexagon' }, { type: 'danger', delay: 6000, z_index: 10000 });
            } else {
                alert(m);
            }
        }, 0);
    }

    showUploadFormatError(msg) {
        const $el = $('#uploadFormatError');
        if ($el.length) {
            $el.text(msg).removeClass('d-none');
        }
    }

    hideUploadFormatError() {
        $('#uploadFormatError').addClass('d-none').text('');
    }

    handleFileAdded(file) {
        this._uploadErrorShown = false;
        this.hideUploadFormatError();
        this.$uploadList.show();
        $('.resumable-progress .progress-resume-link').hide();
        $('.resumable-progress .progress-pause-link').show();
        this.$uploadList.append(`<li class="resumable-file-${file.uniqueIdentifier}">Uploading <span class="resumable-file-name"></span> <span class="resumable-file-progress"></span></li>`);
        $(`.resumable-file-${file.uniqueIdentifier} .resumable-file-name`).html(file.fileName);
        this.resumable.upload();
    }

    handleFileSuccess(file, message) {
        const errMsg = this.parseUploadErrorResponse(message);
        if (errMsg) {
            this.markUploadFailedAndStop(file, errMsg);
            return;
        }
        this.hideUploadFormatError();
        $(`.resumable-file-${file.uniqueIdentifier} .resumable-file-progress`).html('(completed)');
    }

    handleFileError(file, message) {
        const errMsg = this.parseUploadErrorResponse(message) || 'File could not be uploaded.';
        this.markUploadFailedAndStop(file, errMsg);
    }

    markUploadFailedAndStop(file, errMsg) {
        if (this._uploadErrorShown) return;
        this._uploadErrorShown = true;
        $(`.resumable-file-${file.uniqueIdentifier} .resumable-file-progress`).html('(failed)');
        this.showUploadFormatError(errMsg);
        this.showUploadErrorToast(errMsg);
        try { this.resumable.cancel(); } catch (e) {}
    }

    handleFileProgress(file) {
        $(`.resumable-file-${file.uniqueIdentifier} .resumable-file-progress`).html(`${Math.floor(file.progress() * 100)}%`);
        $('.progress-bar').css({ width: `${Math.floor(this.resumable.progress() * 100)}%` });
    }

    bindEvents() {
        $("#uploadFile").on("click", () => this.uploadFile());
        $(this.$importFileSelect).on("change", (e) => this.handleFileSelectChange(e));
        $(document).on("click", "#importFile", (e) => this.importFile(e));
        $(document).on("change select2:clear select2:select", "#replace_records", (e) => this.handleReplaceRecordsChange(e));
    }

    uploadFile() {
        const elem = document.getElementById("file");
        const file = elem.files[0];
        if (typeof file != "undefined" && this.resumable) {
            this.resumable.addFile(file);
            console.log(file);
        }
        elem.value = null;
    }

    handleFileSelectChange(e) {
        const data = $(e.target).select2("data")[0];
        if (data) {
            this.$previewFile.attr("href", data.file_url).show();
        } else {
            this.$previewFile.hide();
        }
    }

    importFile(e) {
        const data = this.$importFileSelect.select2("data")[0];
        const importType = $(this.$importFileSelect).data("import_type");
        if (data) {
            Swal.fire({
                title: `Import ${data.text} File?`,
                confirmButtonText: "Import",
                confirmButtonColor: "#3a772d",
                showDenyButton: true,
                denyButtonText: "Cancel",
                icon: "question"
            }).then((result) => {
                if (result.isConfirmed) {
                    // Serialize the form fields
                    const formFields = $("#common_import_form").serializeArray();

                    // Convert serialized form fields into an object
                    let formData = {};
                    formFields.forEach(field => {
                        formData[field.name] = field.value;
                    });

                    // Merge form fields with the default query
                    const query = Object.assign(
                        {
                            import_id: data.id, replace_records: this.replaceRecords, replace_date: $(this.replaceDate).val(), import_type: importType,
                            _token: this.token,
                        },
                        formData
                    );

                    const fileName = data.text || 'file';
                    const showImportLoader = (fileLabel, processed, total) => {
                        const p = processed ?? 0;
                        const t = total ?? 0;
                        const pStr = typeof Helpers !== 'undefined' && Helpers.AddCommas ? Helpers.AddCommas(p) : p;
                        const tStr = typeof Helpers !== 'undefined' && Helpers.AddCommas ? Helpers.AddCommas(t) : t;
                        $("#importProcessing").html(`<h4 class="mb-5 alert alert-primary d-flex align-items-center justify-content-between">Import ${fileLabel} is in progress! <span class="d-flex align-items-center">${pStr}/${tStr}<span class='spinner-border text-primary mx-3'></span></span></h4>`);
                    };

                    $.ajax({
                        type: 'post',
                        url: $(e.target).attr('data-href'),
                        data: query,
                        beforeSend: () => {
                            $("#importFile").prop("disabled", true);
                            showImportLoader(fileName, 0, 0);
                        },
                        success: (result) => {
                            FileManager._wasProcessing = true;
                            $.notify({ title: 'Success!', message: result.success }, { type: 'success', delay: 4000, z_index: 2000 });
                            if (result.import && result.import.file) {
                                showImportLoader(result.import.file, result.import.processed_records, result.import.total_records);
                            }
                        },
                        error: (err) => {
                            let message = "Unknown error occurred: ";
                            if (err.responseJSON) {
                                message = err.responseJSON.message;
                            }
                            $.notify({ title: 'Error!', message: message, icon: 'cs-error-hexagon' }, { type: 'danger', delay: 4000, z_index: 2000 });
                            $("#importProcessing").html('');
                            $("#importFile").prop("disabled", false);
                        }
                    });
                }
            });
        }
    }

    static _lastShownErrorId = null;
    static _wasProcessing = false;
    static _lastSuccessImportId = null;
    static _successMessageTimeout = null;

    /** Format import_finished_at for display: e.g. "18 Feb 2026, 11:30 AM" */
    static formatImportTime(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        const day = d.getDate();
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const month = months[d.getMonth()];
        const year = d.getFullYear();
        let hours = d.getHours(), minutes = d.getMinutes();
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        minutes = minutes < 10 ? '0' + minutes : minutes;
        return `${day} ${month} ${year}, ${hours}:${minutes} ${ampm}`;
    }

    static checkImportProcess(route, import_type, abortRoute, token, longRunningMinutes) {
        const threshold = (longRunningMinutes !== undefined && longRunningMinutes !== null) ? parseInt(longRunningMinutes, 10) : 30;
        let all = 0;
        if (!import_type) {
            all = 1;
        }
        $.ajax(route, { type: "GET", data: { import_type: import_type, all: all } })
            .done((res) => {
                if (res.status && res.import && res.import.import_type == import_type) {
                    FileManager._wasProcessing = true;
                    $("#importFile").prop("disabled", true);

                    const phase = (res.import.import_phase || '').toLowerCase();
                    const isDeleting = phase === 'deleting';

                    if (isDeleting) {
                        $("#importProcessing").attr('data-abort-route', '').attr('data-abort-token', '');
                        $("#importProcessing").html(`<h4 class="mb-5 alert alert-secondary d-flex align-items-center"><span class="spinner-border text-secondary me-3"></span>Deleting existing data for the selected date… Please wait.</h4>`);
                    } else {
                        const processed = parseInt(res.import.processed_records, 10) || 0;
                        const total = parseInt(res.import.total_records, 10) || 0;
                        const startedAt = res.import.import_started_at ? new Date(res.import.import_started_at.replace(' ', 'T')) : null;
                        const runningMinutes = startedAt ? (Date.now() - startedAt.getTime()) / (60 * 1000) : 0;
                        const showLongRunningWarning = abortRoute && token && startedAt && runningMinutes >= threshold;

                        if (showLongRunningWarning) {
                            const recordText = total > 0
                                ? ` ${Helpers.AddCommas(processed)} of ${Helpers.AddCommas(total)} records have been imported so far.`
                                : '';
                            const timeText = threshold === 0 ? 'has been running' : `has been importing for more than ${threshold} minutes`;
                            const abortBtn = `<button type="button" class="btn btn-danger btn-sm ms-3 js-abort-import-btn" data-import-id="${res.import.id}">Abort import</button>`;
                            $("#importProcessing")
                                .attr('data-abort-route', abortRoute)
                                .attr('data-abort-token', token)
                                .html(`<div class="mb-5 alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2"><div class="flex-grow-1"><strong>Import running ${threshold > 0 ? 'over ' + threshold + ' minutes' : ''}</strong><br>${res.import.file || 'File'} ${timeText}.${recordText} Do you want to abort the import? No further records will be processed.</div>${abortBtn}</div>`);
                        } else {
                            $("#importProcessing").attr('data-abort-route', '').attr('data-abort-token', '');
                            $("#importProcessing").html(`<h4 class="mb-5 alert alert-primary d-flex align-items-center justify-content-between">Import started — ${res.import.file || 'File'} <span class="d-flex align-items-center">${Helpers.AddCommas(processed)}/${Helpers.AddCommas(total)}<span class='spinner-border text-primary mx-3'></span></span></h4>`);
                        }
                    }
                } else {
                    if (FileManager._wasProcessing && res.import && res.import.is_imported == 1 && !res.import.error_message && res.import.id !== FileManager._lastSuccessImportId) {
                        FileManager._lastSuccessImportId = res.import.id;
                        const fileName = res.import.file || 'File';
                        const finishedAt = FileManager.formatImportTime(res.import.import_finished_at);
                        const timeText = finishedAt ? ` at ${finishedAt}` : '';
                        $("#importProcessing").html(`<h4 class="mb-5 alert alert-success d-flex align-items-center">${fileName} imported successfully${timeText}.</h4>`);
                        $.notify({ title: 'Success!', message: `${fileName} imported successfully${timeText}.`, icon: 'cs-check-hexagon' }, { type: 'success', delay: 5000, z_index: 2000 });
                        if (FileManager._successMessageTimeout) clearTimeout(FileManager._successMessageTimeout);
                        FileManager._successMessageTimeout = setTimeout(() => { $("#importProcessing").html(''); FileManager._successMessageTimeout = null; }, 60000);
                    } else if (!$("#importProcessing").find('.alert-success').length) {
                        $("#importProcessing").html('');
                    }
                    FileManager._wasProcessing = false;
                    $("#importFile").prop("disabled", false);
                }
                if (!res.status && res.import && res.import.error_message && res.import.id !== FileManager._lastShownErrorId) {
                    FileManager._lastShownErrorId = res.import.id;
                    $.notify({ title: 'Import Error', message: res.import.error_message, icon: 'cs-error-hexagon' }, { type: 'danger', delay: 6000, z_index: 2000 });
                }
                setTimeout(() => { FileManager.checkImportProcess(route, import_type, abortRoute, token, longRunningMinutes); }, 10000);
            });
    }

    handleReplaceRecordsChange(e) {
        const value = e.target.value;
        this.replaceRecords = false;
        if (value === "yes") {
            $("#date_selection").show();
            this.replaceRecords = true;
        } else {
            $("#date_selection").hide();
        }

        if (value !== "yes" && value !== "no") {
            $(e.target).val("no");
        }
    }

    fileManager() {
        return this;
    }
}
