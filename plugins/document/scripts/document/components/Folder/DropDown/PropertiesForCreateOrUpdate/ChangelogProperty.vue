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
    <div class="tlp-form-element">
        <label class="tlp-label" for="document-update-changelog" v-translate>Changelog</label>
        <textarea
            class="tlp-textarea"
            id="document-update-changelog"
            data-test="document-update-changelog"
            name="changelog"
            v-bind:placeholder="`${$gettext(
                'Please describe the changes of the new version here'
            )}`"
            v-bind:value="value"
            v-on:input="oninput"
        ></textarea>
    </div>
</template>
<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import emitter from "../../../../helpers/emitter";
@Component
export default class ChangelogProperty extends Vue {
    @Prop({ required: true })
    readonly value!: string;

    oninput($event: Event): void {
        if ($event.target instanceof HTMLTextAreaElement) {
            emitter.emit("update-changelog-property", $event.target.value);
        }
    }
}
</script>
