<!--
  - Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
  -
  -->

<template>
    <div class="tlp-form-element document-search-criterion document-search-criterion-text">
        <label class="tlp-label" v-bind:for="id">{{ criterion.label }}</label>
        <input
            type="text"
            class="tlp-input"
            v-bind:id="id"
            v-bind:value="value"
            v-on:input="$emit('input', $event.target.value)"
            v-bind:data-test="id"
        />
    </div>
</template>

<script setup lang="ts">
import type { SearchCriterionText } from "../../../type";
import { computed } from "@vue/composition-api";

const props = defineProps<{ criterion: SearchCriterionText; value: string }>();

const id = computed((): string => {
    return "document-criterion-text-" + props.criterion.name;
});
</script>

<script lang="ts">
import { defineComponent } from "@vue/composition-api";
export default defineComponent({});
</script>
