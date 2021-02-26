var bodyFormData = new FormData();
bodyFormData.append('action', 'wpty_save_job');
var app = new Vue({
    el: "#app",
    data: {
        field: {
            name: '',
            provider: 'email',
            email: '',
            category: 'all',
            priority: 'desc',
            schedule: 'hourly',
            action: 'wpty_save_job',
            content_type: 'full',
            telegram_bot_token: '',
            telegram_channel_username: ''
        },
        processing: false,
        jobs: [],
        history: [],
        history_page: {
            prev_page: false,
            next_page: false,
            pages: 1,
            current_page: 1
        },
        limit: 20,
        search_term: ''
    },
    methods: {
        getData() {
            let data = new FormData()
            data.append('action', 'wpty_get_job')
            data.append('page', this.history_page.current_page)
            data.append('limit', this.limit)
            data.append('term', this.search_term)
            axios.post(ajaxurl, data).then(res => {
                this.jobs = res.data.events
                this.history = res.data.history.data
                this.history_page = res.data.history.button
            })
        },
        saveJob() {
            const getFormData = object => Object.keys(this.field).reduce((formData, key) => {
                formData.append(key, this.field[key]);
                return formData;
            }, new FormData());
            this.processing = true
            axios.post(ajaxurl, getFormData()).then(res => {
                this.processing = false
                if (res.data === 1) {
                    Swal.fire({
                        title: 'Success',
                        text: "Job has been saved",
                        icon: 'success'
                    }).then(res => {
                        location.reload()
                    })
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: res.data
                    })
                }
            }).catch(err => {
                this.processing = false
                Swal.fire({
                    title: 'Error',
                    text: err
                })
            })

        },
        deleteJob(item) {
            Swal.fire({
                title: 'Confirmation',
                html: "Are you sure want to delete this job?",
                confirmButtonText: `Delete`,
                showCancelButton: true,
                icon: 'warning'
            }).then((result) => {
                if (result.isConfirmed) {
                    let data = new FormData()
                    data.append('action', 'wpty_delete_job')
                    data.append('job_id', item.meta.job_id)
                    data.append('timestamp', item.time)
                    data.append('meta', JSON.stringify(item.meta))
                    axios.post(ajaxurl, data).then(res => {
                        if (res.data === 1) {
                            Swal.fire({
                                title: 'Success',
                                text: "Job has been deleted",
                                icon: 'success'
                            })
                            this.getData()
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: res.data
                            })
                        }
                    })

                }
            })
        },
        deleteHistory(item) {
            Swal.fire({
                title: 'Confirmation',
                html: "Are you sure want to delete post <b>" + item.post_title + "</b> from history? Tuyul will send it back via <b>" + item.name + "</b> job (if it's still available)",
                confirmButtonText: `Delete`,
                showCancelButton: true,
                icon: 'warning'
            }).then((result) => {
                if (result.isConfirmed) {
                    let data = new FormData()
                    data.append('action', 'wpty_delete_history')
                    data.append('history_id', item.id)
                    axios.post(ajaxurl, data).then(res => {
                        if (res.data === 1) {
                            Swal.fire({
                                title: 'Success',
                                text: "History has been deleted",
                                icon: 'success'
                            })
                            this.getData()
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: res.data
                            })
                        }
                    })

                }
            })
        },
        runJob(item) {
            Swal.fire({
                title: 'Confirmation',
                html: "Are you sure want to run this job now? This action will send a post to selected provider",
                confirmButtonText: `Run Now`,
                showCancelButton: true,
                icon: 'warning'
            }).then((result) => {
                if (result.isConfirmed) {
                    let data = object => Object.keys(item).reduce((formData, key) => {
                        formData.append(key, item[key]);
                        formData.append('action', 'wpty_run_job')
                        formData.append('meta', JSON.stringify(item.meta))
                        return formData;
                    }, new FormData());

                    axios.post(ajaxurl, data()).then(res => {
                        if (res.data === 1) {
                            Swal.fire({
                                title: 'Success',
                                text: "This job has been executed.",
                                icon: 'success'
                            }).then(res => {
                                this.getData()
                            })

                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: res.data
                            })
                        }
                    })

                }
            })

        }
    },
    mounted() {
        this.getData()
    }
})