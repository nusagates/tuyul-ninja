<?php

namespace App\Includes;

class TuyulMenu {
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'set_menu' ] );
	}

	/**
	 * create admin page menu
	 */
	public function set_menu() {
		add_menu_page( "Tuyul Ninja", "Tuyul Ninja", 'manage_options', 'tuyul-jobs', [ $this, 'job_page' ] , TUYUL_URL.'resources/img/icon.png?m', 70);
		add_submenu_page( 'tuyul-jobs', 'All Jobs', 'All Jobs', 'manage_options', 'tuyul-jobs', [ $this, 'job_page' ] );
		add_submenu_page( 'tuyul-jobs', 'Content Tools', 'Content Tools', 'manage_options', 'content_tool_page', [
			$this,
			'content_tool_page'
		] );
	}

	/**
	 * Display the option page fo the plugin
	 */
	public function job_page() {
		$this->add_dependencies();
		$categories = get_categories();
		$schedules  = wp_get_schedules();
		?>
        <div id="app">
            <div class="container">
                <div class="row">
                    <div class="col-md-4">
                        <h3><?php _e( "Add Job to Tuyul", 'tuyul-ninja' ); ?></h3>
                        <div class="form-group">
                            <label for="name"><?php _e( "Job Name", 'tuyul-ninja' ); ?></label>
                            <input type="text" v-model="field.name" id="name" class="form-control"/>
                        </div>
                        <div class="form-group">
                            <label for="job-list"><?php _e( "Select Provider", 'tuyul-ninja' ); ?></label>
                            <select v-model="field.provider" id="job-list" class="form-control">
                                <option value="email">Email</option>
                                <option value="blogger">Blogger (Publish via Email)</option>
                                <option value="telegram-channel"><?php _e( "Telegram Channel", 'tuyul-ninja' ); ?></option>
                            </select>
                        </div>
                        <form id="form-tb-setting" action="options.php" method="post">
							<?php get_option( 'options' ); ?>
							<?php settings_fields( 'wpty_options' ); ?>
                            <input type="hidden" name="action" value="wpty_save_general_setting">
                            <div v-show="field.provider==='email'||field.provider==='blogger'" class="form-group">
                                <label for="email"><?php _e( "Email", 'tuyul-ninja' ); ?></label>
                                <input v-model="field.email" required type="email"
                                       class="form-control"
                                       id="email"
                                       name="email">
                            </div>
                            <div v-show="field.provider==='telegram-channel'">
                                <div class="form-group">
                                    <label>Telegram Bot API Token</label>
                                    <input v-model="field.telegram_bot_token" type="text" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Telegram Channel Username</label>
                                    <input v-model="field.telegram_channel_username" type="text" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="category"><?php _e( "Category", 'tuyul-ninja' ); ?></label>
                                <select v-model="field.category" id="category" name="category" class="form-control">
                                    <option value="all"><?php _e( "All" ); ?></option>
									<?php

									foreach ( $categories as $item ) {
										echo "<option value='$item->term_id'>$item->name</option>";
									}
									?>
                                </select>
                                <small><?php _e( "tell tuyul to choose which post category to send ", 'tuyul-ninja' ); ?></small>
                            </div>
                            <div class="form-group">
                                <label for="priority"><?php _e( "Priority", 'tuyul-ninja' ); ?></label>
                                <select v-model="field.priority" id="priority" name="priority" class="form-control">
                                    <option value="random">
										<?php _e( "Random", 'tuyul-ninja' ); ?>
                                    </option>
                                    <option value="desc"><?php _e( "Newer Post First", 'tuyul-ninja' ); ?></option>
                                    <option value="asc"><?php _e( "Older Post First", 'tuyul-ninja' ); ?></option>
                                </select>
                                <small><?php _e( "tell tuyul to choose which post to send first", 'tuyul-ninja' ); ?></small>
                            </div>
                            <div class="form-group">
                                <label for="schedule"><?php _e( "Send Schedule", 'tuyul-ninja' ); ?></label>
                                <select v-model="field.schedule" id="schedule" class="form-control" name="schedule">
									<?php
									foreach ( $schedules as $key => $schedule ) {
										echo '<option value="' . $key . '">' . $schedule['display'] . '</option>';
									}
									?>
                                </select>
                                <small><?php _e( "how often the post will be sent by Tuyul", "tuyul-ninja" ); ?></small>
                            </div>
                            <div class="form-group">
                                <label>Send Post Content as:</label><br>
                                <input value="full" type="radio" id="full" name="is_full" v-model="field.content_type"
                                       checked>
                                <label for="full">Full Content</label><br>
                                <input value="excerpt" type="radio" id="is_full2" name="is_full"
                                       v-model="field.content_type">
                                <label for="is_full2">Excerpt</label><br>
                            </div>
                            <div class="form-group">
                                <button :disabled="processing" @click.prevent="saveJob"
                                        class="btn btn-success w-100">
                                    <span v-if="processing"><?php _e( 'saving...', 'tuyul-ninja' ) ?></span>
                                    <span v-else><?php _e( 'Add Job', 'Tuyul Ninja' ); ?></span>
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-8">
                        <h3><?php _e( 'Existing Job', 'tuyul-ninja' ) ?></h3>
                        <div class="table-responsive">
                            <table class="table table-striped w-100">
                                <thead>
                                <tr>
                                    <th><?php _e( 'Name', 'tuyul-ninja' ) ?></th>
                                    <th><?php _e( 'Provider', 'tuyul-ninja' ) ?></th>
                                    <th><?php _e( 'Category', 'tuyul-ninja' ) ?></th>
                                    <th><?php _e( 'Meta', 'tuyul-ninja' ) ?></th>
                                    <th><?php _e( 'Action', 'tuyul-ninja' ) ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-if="jobs.length>0" v-for="(item, index) of jobs">
                                    <td class="align-middle" v-html="item.meta.data.name"/>
                                    <td class="align-middle" v-html="item.meta.data.provider"/>
                                    <td class="align-middle" v-html="item.meta.data.category_name"/>
                                    <td class="align-middle">
                                        <ol style="list-style: none">
                                            <li v-if="item.meta.data.provider==='email'"><?php _e( 'Email', 'tuyul-ninja' ) ?>
                                                :
                                                {{item.meta.data.email}}
                                            </li>
                                            <li v-if="item.meta.data.provider==='telegram-channel'"><?php _e( 'Channel', 'tuyul-ninja' ) ?>
                                                :
                                                {{item.meta.data.telegram_channel_username}}
                                            </li>
                                            <li><?php _e( 'Schedule', 'tuyul-ninja' ) ?>: {{item.schedule}}
                                            </li>
                                            <li v-if="item.meta.data.priority==='desc'"><?php _e( 'Priority: Newer first', 'tuyul-ninja' ) ?>
                                            </li>
                                            <li v-if="item.meta.data.priority==='asc'"><?php _e( 'Priority: Older first', 'tuyul-ninja' ) ?></li>
                                            <li v-if="item.meta.data.priority==='random'"><?php _e( 'Priority: Random', 'tuyul-ninja' ) ?></li>
                                        </ol>
                                    </td>
                                    <td class="text-center align-middle">
                                        <button @click="runJob(item)"
                                                class="btn btn-success btn-sm mb-1"><?php _e( 'Run', 'tuyul-ninja' ) ?></button>
                                        <button @click="deleteJob(item)"
                                                class="btn btn-danger btn-sm mb-1"><?php _e( 'Delete', 'tuyul-ninja' ) ?>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="jobs.length<1">
                                    <td colspan="5"
                                        class="text-center"><?php _e( "No job available", 'tuyul-digital' ); ?></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <hr/>
                        <h3><?php _e( 'History', 'tuyul-ninja' ) ?></h3>
                        <div class="d-flex justify-content-between">
                            <div class="input-group mb-3 w-25">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Show</span>
                                </div>
                                <select @change="getData" v-model="limit" class="form-control">
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <span class="input-group-text">entries</span>
                            </div>
                            <div class="input-group mb-3 w-25">
                                <span class="input-group-text">Search</span>
                                <input @keyup="getData" v-model="search_term"
                                       placeholder="<?php _e( 'Serach by post title', 'tuyul-ninja' ) ?>" type="search"
                                       class="form-control"/>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="table-history" class="table table-striped">
                                <thead>
                                <tr>
                                    <th><?php _e( 'Post Title', 'tuyul-ninja' ) ?></th>
                                    <th><?php _e( 'Job', 'tuyul-ninja' ) ?></th>
                                    <th><?php _e( 'Sent', 'tuyul-ninja' ) ?></th>
                                    <th><?php _e( 'Action', 'tuyul-ninja' ) ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-if="history.length>0" v-for="(item, index) of history">
                                    <td v-html="item.post_title"/>
                                    <td v-html="item.name"/>
                                    <td v-html="item.created_at"/>
                                    <td>
                                        <button @click="deleteHistory(item)"
                                                class="btn btn-sm btn-danger"><?php _e( 'Delete', 'tuyul-ninja' ) ?>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="history.length<1">
                                    <td colspan="4"
                                        class="text-center"><?php _e( 'No execution histories', 'tuyul-ninja' ) ?></td>
                                </tr>
                                </tbody>
                            </table>
							<?php _( 'Run', 'tuyul-ninja' ) ?>
                        </div>
                        <div v-if="history_page.total_pages>1" class="d-flex justify-content-between">
                            <div>
                                Showing {{history_page.start_entry}} to {{history_page.end_entry}} of
                                {{history_page.total_entry}} entries
                            </div>
                            <ul class="pagination">
                                <li class="page-item" v-bind:class="{disabled:history_page.prev_page===false}">
                                    <a @click.prevent="getData(history_page.prev_page)" class="page-link" href="#"
                                       aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                        <span class="sr-only">Previous</span>
                                    </a>
                                </li>
                                <li class="page-item" v-bind:class="{disabled:history_page.next_page===false}">
                                    <a @click.prevent="getData(history_page.next_page)" class="page-link" href="#"
                                       aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                        <span class="sr-only">Next</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>
		<?php
	}

	public function content_tool_page() {
		$this->add_dependencies( 'content-tool' );
		$categories = get_categories();
		?>
        <div id="app">
            <div class="container">
                <h3>Content Tools</h3>
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="content-ideas-tab" data-toggle="tab" href="#content-ideas"
                           role="tab" aria-controls="content-ides" aria-selected="true">Content Ideas</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="keyword-trends-tab" data-toggle="tab" href="#keyword-trends" role="tab"
                           aria-controls="keyword-trends" aria-selected="false">Keyword Trends</a>
                    </li>
                </ul>
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="content-ideas" role="tabpanel"
                         aria-labelledby="content-ideas-tab">
                        <p>Grab content ideas using specific topic.</p>
                        <div class="text-center">
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="basic-addon1">TOPIC</span>
                                </div>
                                <input @keyup.enter="getIdeas" v-model="topic" type="text" class="form-control"
                                       placeholder="e.g: home decor"
                                       aria-label="Topic"
                                       aria-describedby="basic-addon1">
                                <div @click="getIdeas" class="input-group-append">
                                    <button :disabled="processing" class="btn btn-outline-success" type="button">
                                        <span v-if="!processing">Get Ideas</span>
                                        <span v-if="processing">Processing...</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between my-3">
                            <div class="align-middle my-auto">
                                {{selected_ideas.length}} selected of {{suggested_ideas.length}} suggested ideas
                            </div>
                            <div v-show="selected_ideas.length>0">
                                <button @click="openDraftModal" class="btn btn-outline-success">Save as Draft</button>
                                <a :href="download_content" :download="download_name" @click="downloadSelectedIdeas"
                                   class="btn btn-outline-info">Download</a>
                                <button @click="removeSelectedIdeas" class="btn btn-outline-danger">Remove</button>
                            </div>
                        </div>
                        <table class="table table-striped table-sm">
                            <thead>
                            <tr>
                                <th width="30"><input @change="selectedAllState" id="select-all" type="checkbox"
                                                      v-model="selected_all"/></th>
                                <th><label for="select-all">Suggested Idea</label></th>
                                <th width="210">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-if="suggested_ideas.length>0" v-for="(item, index) of suggested_ideas">
                                <td><input :id="'idea-'+index" :value="item" type="checkbox" name="ideas"
                                           v-model="selected_ideas"/></td>
                                <td>
                                    <label :for="'idea-'+index" v-html="item"/>
                                </td>
                                <td>
                                    <button @click="topic=item;getIdeas()" class="btn btn-success btn-sm">Related Ideas
                                    </button>
                                    <button @click="removeIdea(index)" class="btn btn-danger btn-sm">Remove Idea
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="suggested_ideas.length<1">
                                <td colspan="3" class="text-center">No ideas</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="tab-pane fade" id="keyword-trends" role="tabpanel" aria-labelledby="keyword-trends-tab">
                        <p>Get all trends keyword by Google Trends</p>
                        <div class="text-center">
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="basic-addon1">COUNTRY</span>
                                </div>
                                <select v-model="trend_country" class="form-control">
                                    <option selected="selected" value="p19">Indonesia</option>
                                    <option value="p40">Afrika Selatan</option>
                                    <option value="p1">Amerika Serikat</option>
                                    <option value="p36">Arab Saudi</option>
                                    <option value="p30">Argentina</option>
                                    <option value="p8">Australia</option>
                                    <option value="p44">Austria</option>
                                    <option value="p17">Belanda</option>
                                    <option value="p41">Belgia</option>
                                    <option value="p18">Brasil</option>
                                    <option value="p38">Cile</option>
                                    <option value="p49">Denmark</option>
                                    <option value="p25">Filipina</option>
                                    <option value="p50">Finlandia</option>
                                    <option value="p10">Hong Kong</option>
                                    <option value="p45">Hungaria</option>
                                    <option value="p3">India</option>
                                    <option value="p9">Inggris</option>
                                    <option value="p6">Israel</option>
                                    <option value="p27">Italia</option>
                                    <option value="p4">Jepang</option>
                                    <option value="p15">Jerman</option>
                                    <option value="p13">Kanada</option>
                                    <option value="p37">Kenya</option>
                                    <option value="p32">Kolombia</option>
                                    <option value="p23">Korea Selatan</option>
                                    <option value="p34">Malaysia</option>
                                    <option value="p21">Meksiko</option>
                                    <option value="p29">Mesir</option>
                                    <option value="p52">Nigeria</option>
                                    <option value="p51">Norwegia</option>
                                    <option value="p31">Polandia</option>
                                    <option value="p47">Portugal</option>
                                    <option value="p16">Prancis</option>
                                    <option value="p43">Republik Cheska</option>
                                    <option value="p39">Rumania</option>
                                    <option value="p14">Rusia</option>
                                    <option value="p5">Singapura</option>
                                    <option value="p26">Spanyol</option>
                                    <option value="p42">Swedia</option>
                                    <option value="p46">Swiss</option>
                                    <option value="p12">Taiwan</option>
                                    <option value="p33">Thailand</option>
                                    <option value="p24">Turki</option>
                                    <option value="p35">Ukraina</option>
                                    <option value="p28">Vietnam</option>
                                    <option value="p48">Yunani</option>
                                </select>
                                <div @click="getTrendKeyword" class="input-group-append">
                                    <button :disabled="processing" class="btn btn-outline-success" type="button">
                                        <span v-if="!processing">Go Now</span>
                                        <span v-if="processing">Processing...</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <table class="table table-striped table-sm">
                            <thead>
                            <tr>
                                <th>Trend Keywords</th>
                                <th width="210">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-if="trend_keyword.length>0" v-for="(item, index) of trend_keyword">
                                <td v-html="item[0]"/>
                                <td>
                                    <button @click="getRelatedKeyword(item[0])" class="btn btn-success btn-sm">Get
                                        Related Keyword
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="trend_keyword.length<1">
                                <td colspan="3" class="text-center">No ideas</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="modal fade" id="modal-draft" tabindex="-1" aria-labelledby="exampleModalLabel"
                         aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Save Ideas as Post Draft</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <p>This action will create multiple post drafts using the selected ideas. The ideas
                                        will be used as post titles.</p>
                                    <div class="form-group">
                                        <label>Post Category</label>
                                        <select v-model="post_category" class="form-control w-100">
											<?php
											foreach ( $categories as $category ) {
												echo "<option value='$category->term_id'>$category->name</option>";
											}
											?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Tags</label>
                                        <textarea v-model="post_tags" class="form-control"></textarea>
                                        <small>Multiple tags sparated by comma(,)</small>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button :disabled="processing" @click="draftSelectedIdeas" type="button"
                                            class="btn btn-outline-success">Create
                                        <span v-if="!processing">Draft Now</span>
                                        <span v-if="processing">Processing...</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
		<?php
	}

	/**
	 * Adding bootstrap and sweetalert to the page
	 */
	private function add_dependencies( $page = '' ) {
		// JS
		wp_enqueue_script( 'wpty_bootstrap', TUYUL_URL . 'resources/js/bootstrap.min.js' );
		wp_enqueue_script( 'wpty_swal2', TUYUL_URL . 'resources/js/sweetalert2.js' );
		wp_enqueue_script( 'wpty_vue', TUYUL_URL . 'resources/js/vue.js' );
		wp_enqueue_script( 'wpty_axios', TUYUL_URL . 'resources/js/axios.min.js' );
		switch ( $page ) {
			case '':
				wp_enqueue_script( 'wpty_functions', TUYUL_URL . 'resources/js/jobs.js' );
				break;
			case 'content-tool':
				wp_enqueue_script( 'wpty_functions', TUYUL_URL . 'resources/js/content-tool.js' );
				break;
		}

		// CSS
		wp_enqueue_style( 'wpty_bootstrap', TUYUL_URL . 'resources/css/bootstrap.min.css' );
	}
}