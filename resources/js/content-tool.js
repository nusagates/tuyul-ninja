let $ = jQuery
var app = new Vue({
    el: "#app",
    data: {
        topic: '',
        suggested_ideas: ['makan'],
        selected_ideas: [],
        selected_all: false,
        download_name: 'ideas.txt',
        download_content: '',
        processing: false
    },
    methods: {
        getIdeas() {
            this.processing = true
            $.ajax({
                url: "https://suggestqueries.google.com/complete/search",
                jsonp: "jsonp",
                dataType: "jsonp",
                data: {
                    q: this.topic,
                    client: "chrome"
                },
                success: (res => {
                    this.processing = false
                    result = res[1]
                    for (let i = 0; i < result.length; i++) {
                        this.suggested_ideas.push(result[i])
                    }
                }),
                err: (err => {
                    this.processing = false
                })
            });
        },
        removeIdea(index) {
            this.suggested_ideas.splice(index, 1);
        },
        selectedAllState() {
            if (this.selected_all) {
                this.selected_ideas = this.suggested_ideas
            } else {
                this.selected_ideas = []
            }
        },
        removeSelectedIdeas() {
            Swal.fire({
                title: 'Confirmation',
                html: "Are you sure want to remove selected ideas?",
                confirmButtonText: `Remove`,
                showCancelButton: true,
                icon: 'warning'
            }).then((result) => {
                if (result.isConfirmed) {
                    let selected_ideas = this.selected_ideas;
                    for (let i = 0; i < selected_ideas.length; i++) {
                        let idea_index = this.suggested_ideas.indexOf(selected_ideas[i]);
                        this.suggested_ideas.splice(idea_index, 1)
                    }
                    this.selected_ideas = []
                }
            })

        },
        downloadSelectedIdeas() {
            let ideas = this.selected_ideas;
            this.download_name = ideas[0]+'.txt'
            let content = ideas.length + ' ideas related to ' + ideas[0] + '\n\n';
            for (let i = 0; i < ideas.length; i++) {
                content += ideas[i] + '\n';
            }
            content+='\n\n'
            content+='===================================================='
            content+='\n\n'
            content += 'This ideas is generated by Tuyul Ninja Plugin\n';
            content += 'PLUGIN URL: https://wordpress.org/plugins/tuyul-ninja/';
            this.download_content = 'data:text/plain;charset=utf-8,' + encodeURIComponent(content);
        }
    },
    mounted() {
    }
})