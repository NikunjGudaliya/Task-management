class Leave {
    constructor(options=null) {
        this.requestMethod = "GET";
        this.requestObject = {};
        this.balanceUrl = options.balanceUrl;
        this.processLeaveDates = options.processLeaveDates;
    }

    setRequestMethod(method = "GET") {
        this.requestMethod = method;
    }

    getRequestMethod() {
        return this.requestMethod;
    }

    setRequestObject(leaveRequest) {
        this.requestObject = leaveRequest;
    }

    getRequestObject() {
        return this.requestObject;
    }

    setRequestObjectOption(key, value) {
        this.requestObject[key] = value;
    }

    getRequestObjectOption(key) {
        return this.requestObject[key];
    }

    startLoader() {
        document.body.classList.add('spinner');
    }

    stopLoader() {
        document.body.classList.remove('spinner');
    }

    async makeRequest(url) {
        this.startLoader();
        let response;
        try {
            response = await $.ajax({
                url: url,
                type: this.requestMethod,
                data: this.requestObject,
            });
        } catch (error) {
            console.error("AJAX Request Failed:", error);
            response = null;
        } finally {
            this.stopLoader();
        }
        return response;
    }

    async getUserLeaveBalance() {
        return await this.makeRequest(this.balanceUrl);
    }

    async getLeaveDays() {
        const leaveDays = await this.makeRequest(this.processLeaveDates);
        if (leaveDays) {
            const {blackdays, holidays, leave_days, pre_approved_leaves, week_offs, without_pay, can_apply, message} = leaveDays;

            if (!can_apply) {
                Swal.fire({
                    icon: 'error',
                    title: 'Cannot Apply Leave',
                    text: message
                });

                return false;
            }
        }
        return leaveDays;
    }
}
