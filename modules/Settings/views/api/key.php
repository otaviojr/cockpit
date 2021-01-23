<kiss-container class="kiss-margin-large" size="small">

    <vue-view>
        <template>

            <h1 class="kiss-margin-large-bottom">
                <span v-if="!key._id"><?=_t('Create key')?></span>
                <span v-if="key._id"><?=_t('Edit key')?></span>
            </h1>

            <form :class="{'kiss-disabled':saving}" @submit.prevent="save">

                <div class="kiss-margin">
                    <label><?=_t('Name')?></label>
                    <input class="kiss-input" type="text" v-model="key.name" required>
                </div>

                <kiss-card class="kiss-margin kiss-margin-large-top kiss-padding" theme="bordered">
                    <label><?=_t('API Key')?></label>
                    <div class="kiss-flex kiss-flex-middle">
                        <div class="kiss-flex-1 kiss-margin-small-right kiss-text-truncate kiss-disabled">
                            <span class="kiss-text-caption" v-if="!key.key"><?=_t('No api key created yet')?></span>
                            <span class="kiss-text-monospace kiss-text-bold" v-if="key.key">{{ key.key }}</span>
                        </div>
                        <a @click="generateToken"><icon>refresh</icon></a>
                        <a class="kiss-margin-small-left" v-if="key.key" @click="copyToken"><icon>content_copy</icon></a>
                    </div>
                </kiss-card>

                <div class="kiss-margin-large kiss-flex kiss-flex-middle">
                    <button type="submit" class="kiss-button kiss-button-primary">
                        <span v-if="!key._id"><?=_t('Create key')?></span>
                        <span v-if="key._id"><?=_t('Update key')?></span>
                    </button>
                    <a class="kiss-margin-left kiss-button kiss-button-link" href="<?=$this->route('/settings/api')?>">
                        <span v-if="!key._id"><?=_t('Cancel')?></span>
                        <span v-if="key._id"><?=_t('Close')?></span>
                    </a>
                </div>

            </form>

        </template>

        <script type="module">

            export default {
                data() {

                    return {
                        saving: false,
                        key: <?=json_encode($key)?>
                    };
                },

                methods: {

                    save() {

                        let isUpdate = this.key._id;

                        this.saving = true;

                        App.request('/settings/api/save', {key: this.key}).then(key => {

                            this.key = key;
                            this.saving = false;

                            if (isUpdate) {
                                App.ui.notify('Api key updated!');
                            } else {
                                App.ui.notify('Api key created!');
                            }
                        }).catch(res => {
                            this.saving = false;
                            App.ui.notify(res.error || 'Saving failed!', 'error');
                        })

                    },

                    generateToken() {

                        App.request('/utils/generateToken').then(res => {
                            this.key.key = `API-${res.token}`;
                        });
                    },
                    copyToken() {
                        App.utils.copyText(this.key.key, () => {
                            App.ui.notify('Api key copied!');
                        });
                    },
                }
            }
        </script>

    </vue-view>

</kiss-container>