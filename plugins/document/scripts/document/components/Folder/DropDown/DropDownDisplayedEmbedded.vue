<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
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
    <drop-down-menu v-bind:item="currently_previewed_item">
        <template v-if="currently_previewed_item.user_can_write">
            <lock-item
                v-bind:item="currently_previewed_item"
                data-test="document-dropdown-menu-lock-item"
                slot="lock-item"
            />
            <unlock-item
                v-bind:item="currently_previewed_item"
                data-test="document-dropdown-menu-unlock-item"
                slot="unlock-item"
            />
            <drop-down-separator
                slot="display-item-title-separator"
                data-test="document-dropdown-separator"
            />
            <update-properties
                v-bind:item="currently_previewed_item"
                data-test="document-update-properties"
                slot="update-properties"
                v-if="should_display_update_properties"
            />
            <update-permissions v-bind:item="currently_previewed_item" slot="update-permissions" />
            <drop-down-separator slot="delete-item-separator" v-if="can_user_delete_item" />
            <delete-item
                v-bind:item="currently_previewed_item"
                role="menuitem"
                data-test="document-delete-item"
                slot="delete-item"
                v-if="can_user_delete_item"
            />
        </template>
    </drop-down-menu>
</template>

<script lang="ts">
import DropDownMenu from "./DropDownMenu.vue";
import DropDownSeparator from "./DropDownSeparator.vue";
import DeleteItem from "./Delete/DeleteItem.vue";
import LockItem from "./Lock/LockItem.vue";
import UnlockItem from "./Lock/UnlockItem.vue";
import UpdateProperties from "./UpdateProperties/UpdateProperties.vue";
import UpdatePermissions from "./UpdatePermissions.vue";
import { Component, Vue } from "vue-property-decorator";
import { namespace, State } from "vuex-class";
import type { Embedded } from "../../../type";
import { canUpdateProperties } from "../../../helpers/can-update-properties-helper";
import { canDelete } from "../../../helpers/can-delete-helper";

const configuration = namespace("configuration");

@Component({
    components: {
        UpdateProperties,
        UpdatePermissions,
        UnlockItem,
        LockItem,
        DeleteItem,
        DropDownSeparator,
        DropDownMenu,
    },
})
export default class DropDownDisplayedEmbedded extends Vue {
    @State
    readonly currently_previewed_item!: Embedded;

    @configuration.State
    readonly forbid_writers_to_update!: boolean;

    @configuration.State
    readonly forbid_writers_to_delete!: boolean;

    get can_user_delete_item(): boolean {
        return (
            this.currently_previewed_item.user_can_write &&
            canDelete(this.forbid_writers_to_delete, this.currently_previewed_item) &&
            Boolean(this.currently_previewed_item.parent_id)
        );
    }

    get should_display_update_properties(): boolean {
        return canUpdateProperties(this.forbid_writers_to_update, this.currently_previewed_item);
    }
}
</script>
