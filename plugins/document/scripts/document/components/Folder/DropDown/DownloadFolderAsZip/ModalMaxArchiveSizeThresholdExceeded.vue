<!--
  - Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
  -
  - This item is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div
        class="tlp-modal tlp-modal-danger"
        role="dialog"
        aria-labelledby="max-size-threshold-modal-label"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="max-size-threshold-modal-label" v-translate>
                Maximum archive size threshold exceeded
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="$gettext('Close')"
            >
                <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <p v-translate>
                The size of the zip file you are attempting to download is exceeding the threshold
                defined by the site administrators.
            </p>
            <p v-translate>
                Contact your administrator or try to reorganize this folder, then try again.
            </p>
            <div class="tlp-alert-danger">
                <span v-translate="{ max_archive_size }">
                    Maximum archive size allowed: %{ max_archive_size } MB
                </span>
                <br />
                <span data-test="download-as-zip-folder-size" v-translate="{ size_in_MB }">
                    Size of the archive to be downloaded: %{ size_in_MB } MB
                </span>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-danger tlp-button-primary tlp-modal-action"
                data-dismiss="modal"
                data-test="close-max-archive-size-threshold-exceeded-modal"
                v-translate
            >
                Got it
            </button>
        </div>
    </div>
</template>
<script lang="ts">
import type { Modal } from "tlp";
import { createModal } from "tlp";
import { Component, Prop, Vue } from "vue-property-decorator";
import { namespace } from "vuex-class";

const configuration = namespace("configuration");

@Component
export default class ModalMaxArchiveSizeThresholdExceeded extends Vue {
    @Prop({ required: true })
    readonly size!: number;

    @configuration.State
    readonly max_archive_size!: number;

    private modal: Modal | null = null;

    mounted(): void {
        this.modal = createModal(this.$el);
        this.modal.addEventListener("tlp-modal-hidden", this.close);
        this.modal.show();
    }

    get size_in_MB(): string {
        const size_in_mb = this.size / Math.pow(10, 6);
        return Number.parseFloat(size_in_mb.toString()).toFixed(2);
    }

    close(): void {
        this.$emit("download-as-zip-modal-closed");
    }
}
</script>
