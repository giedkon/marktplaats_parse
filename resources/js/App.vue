<template>

    <v-app>
        <v-container>
            <v-card elevation="6">
                <v-card-title>
                    ORCA Task // Giedrius Kondrotas
                </v-card-title>
                <v-card-subtitle>
                    Using Laravel v8.12 for back-end with Vue.js & Vuetify for front-end. <br>
                    Intervention/Image for image processing.
                </v-card-subtitle>
                <v-card-text>
                    <v-alert v-if="error" color="error">{{ error }}</v-alert>
                </v-card-text>
                <v-card-text>
                    <v-row>
                        <v-col>
                            <v-text-field v-model="url" :loading="loading" append-icon="mdi-location-enter" label="URL"
                                          @click:append="getScrape()">

                            </v-text-field>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col>
                            <v-select v-model="selectAgent" :hint="`${selectAgent.agent}`"
                                      :items="itemsAgent"
                                      dense
                                      item-text="name"
                                      item-value="agent"
                                      label="CURL AGENT"
                                      persistent-hint
                                      return-object
                                      single-line>
                            </v-select>
                        </v-col>
                        <v-col>
                            <v-textarea v-model="headers"
                                        dense
                                        label="Headers (seperate by newline)"
                                        rows="1"
                            ></v-textarea>
                        </v-col>
                        <v-col>
                            <v-text-field v-model="proxy"
                                          dense
                                          label="Proxy (ip:port)"
                            ></v-text-field>
                        </v-col>
                    </v-row>
                </v-card-text>
                <v-divider v-if="response"></v-divider>
                <v-card-text v-if="response">
                    <v-row>
                        <v-col>
                            <v-img v-if="response" :max-height="expanded? 500 : 250"
                                   :src="expanded ? response.image_large : response.image_thumb"
                                   contain style="cursor: pointer"
                                   @click="expanded = !expanded">
                            </v-img>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col>
                            Data from <a :href="url">{{ url }}</a>
                        </v-col>
                    </v-row>
                </v-card-text>
                <v-card-text v-if="response">
                    <v-row class="font-weight-bold">
                        <v-col>
                            Attribute
                        </v-col>
                        <v-col>
                            Value
                        </v-col>
                    </v-row>
                </v-card-text>
                <v-divider v-if="response" class="pb-2"></v-divider>
                <v-card-text>
                    <v-row v-for="(response, key) in this.response">
                        <v-col>
                            {{ key }}
                        </v-col>
                        <v-col>
                            {{ response }}
                        </v-col>
                    </v-row>
                </v-card-text>
            </v-card>
        </v-container>
    </v-app>

</template>

<script>
export default {
    name: "App",
    data() {
        return {
            url: "",
            expanded: false,
            response: null,
            error: null,
            loading: false,
            proxy: "",
            headers: "",
            selectAgent: {
                name: 'Chrome',
                agent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36'
            },
            itemsAgent: [
                {
                    name: 'Chrome',
                    agent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36'
                },
                {
                    name: 'Android',
                    agent: 'Mozilla/5.0 (Linux; U; Android 2.2) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1'
                },
                {
                    name: 'Apple iPhone',
                    agent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 12_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148'
                },
            ],
        }
    },
    methods: {
        getScrape() {
            this.response = null;
            this.loading = true;
            axios.get('api/getScrape', {
                params: {
                    url: this.url,
                    agent: this.selectAgent.agent,
                    headers: this.headers.split('\n'),
                    proxy: this.proxy,
                }
            }).then(response => {
                console.log(response.data);
                this.response = JSON.parse(response.data);
                this.loading = false;
                this.error = null;
            }).catch(error => {
                console.log(error.data);
                this.error = error.response.data;
                this.loading = false;
            })
        }
    }
}
</script>
