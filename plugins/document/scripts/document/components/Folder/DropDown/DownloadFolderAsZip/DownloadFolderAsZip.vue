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
    <button
        type="button"
        class="tlp-dropdown-menu-item"
        role="menuitem"
        data-test="download-as-zip-button"
        v-on:click="checkFolderSize"
        data-shortcut-download-zip
    >
        <i
            class="fa fa-fw tlp-dropdown-menu-item-icon"
            v-bind:class="{
                'fa-tlp-zip-download': !is_retrieving_folder_size,
                'fa-spin fa-circle-o-notch': is_retrieving_folder_size,
            }"
        ></i>
        <translate>Download as zip</translate>
    </button>
</template>
<script lang="ts">
import { redirectToUrl } from "../../../../helpers/location-helper";
import emitter from "../../../../helpers/emitter";
import { Component, Prop, Vue } from "vue-property-decorator";
import type { Item } from "../../../../type";
import { namespace } from "vuex-class";
import { isPlatformOSX } from "../../../../helpers/platform-detector";

const configuration = namespace("configuration");

@Component
export default class DownloadFolderAsZip extends Vue {
    @Prop({ required: true })
    readonly item!: Item;

    private is_retrieving_folder_size = false;

    @configuration.State
    readonly project_name!: string;

    @configuration.State
    readonly max_archive_size!: number;

    @configuration.State
    readonly warning_threshold!: number;

    get folder_href(): string {
        return `/plugins/document/${this.project_name}/folders/${encodeURIComponent(
            this.item.id
        )}/download-folder-as-zip`;
    }

    shouldWarnOSXUser(total_size: number, nb_files: number): boolean {
        if (!isPlatformOSX(window)) {
            return false;
        }

        const total_size_in_GB = total_size * Math.pow(10, -9);
        return total_size_in_GB >= 4 || nb_files >= 64000;
    }
    async checkFolderSize(): Promise<void> {
        this.is_retrieving_folder_size = true;

        const folder_properties = await this.$store.dispatch(
            "properties/getFolderProperties",
            this.item
        );
        this.is_retrieving_folder_size = false;

        if (folder_properties === null) {
            return;
        }

        // max_archive_size is in MB, total_size in Bytes. Let's convert it to Bytes first.
        const max_archive_size_in_Bytes = this.max_archive_size * Math.pow(10, 6);
        const { total_size, nb_files } = folder_properties;

        if (total_size > max_archive_size_in_Bytes) {
            emitter.emit("show-max-archive-size-threshold-exceeded-modal", {
                detail: { current_folder_size: total_size },
            });

            return;
        }

        // warning_threshold is in MB, total_size in Bytes. Let's convert it to Bytes first.
        const warning_threshold_in_Bytes = this.warning_threshold * Math.pow(10, 6);
        const should_warn_osx_user = this.shouldWarnOSXUser(total_size, nb_files);

        if (total_size > warning_threshold_in_Bytes) {
            emitter.emit("show-archive-size-warning-modal", {
                detail: {
                    current_folder_size: total_size,
                    folder_href: this.folder_href,
                    should_warn_osx_user,
                },
            });

            return;
        }

        redirectToUrl(this.folder_href);
    }
}
</script>
