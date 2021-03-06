<template>
    <v-dialog :value="value"
              @input="$emit('input', $event)"
              max-width="400"
              lazy
    >
        <v-card>
            <v-card-title>
                <v-icon>mdi-cart-plus</v-icon>
                <span class="headline">Position entfernen</span>
            </v-card-title>
            <v-card-text>
                <v-container fluid grid-list-md>
                    <v-layout row wrap>
                        <v-flex xs12>
                            Möchten Sie Position {{line ? line.POSITION : ''}} entfernen?
                        </v-flex>
                    </v-layout>
                </v-container>
            </v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn flat @click="$emit('input', false)">Abbrechen</v-btn>
                <v-btn color="error"
                       :loading="deleting"
                       @click="onDelete"
                >
                    <v-icon>add</v-icon>
                    Entfernen
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script lang="ts">
    import Vue from "vue";
    import Component from "vue-class-component";
    import {namespace, Mutation} from "vuex-class";
    import {Prop} from "vue-property-decorator";
    import EntitySelect from "../../../common/EntitySelect.vue"
    import {Invoice, InvoiceLine} from "../../../../server/resources/models";

    const SnackbarMutation = namespace('shared/snackbar', Mutation);
    const RefreshMutation = namespace('shared/refresh', Mutation);

    @Component({components: {'app-entity-select': EntitySelect}})
    export default class DetailView extends Vue {
        @Prop({type: Boolean})
        value: boolean;

        @Prop({type: Object})
        invoice: Invoice;

        @Prop({type: Object})
        line: InvoiceLine;

        @SnackbarMutation('updateMessage')
        updateMessage: Function;

        @RefreshMutation('requestRefresh')
        requestRefresh: Function;

        deleting: boolean = false;

        onDelete() {
            this.deleting = true;
            this.line.delete().then(() => {
                this.deleting = false;
                this.updateMessage('Position entfernt.');
                this.requestRefresh();
                this.$emit('input', false);
                this.$emit('deleted', this.line);
            }).catch(error => {
                this.deleting = false;
                this.updateMessage('Fehler beim Entfernen der Position. Code: ' + error.response.status + ' Message: ' + error.response.statusText);
            });
        }
    }
</script>