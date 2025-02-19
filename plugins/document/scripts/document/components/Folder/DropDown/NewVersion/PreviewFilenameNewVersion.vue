<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
  -
  -->

<template>
    <preview-filename-property v-bind:item="item">
        {{ preview }}
    </preview-filename-property>
</template>

<script setup lang="ts">
import type { NewVersion } from "../../../../type";
import { computed } from "@vue/composition-api";
import { useNamespacedState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../../store/configuration";
import { addOriginalFilenameExtension } from "../../../../helpers/add-original-filename-extension";
import type { DefaultFileNewVersionItem } from "../../../../type";
import PreviewFilenameProperty from "../../ModalCommon/PreviewFilenameProperty.vue";

const props = defineProps<{ version: NewVersion; item: DefaultFileNewVersionItem }>();

const { filename_pattern } = useNamespacedState<ConfigurationState>("configuration", [
    "filename_pattern",
]);

const preview = computed((): string => {
    return addOriginalFilenameExtension(
        filename_pattern.value
            // eslint-disable-next-line no-template-curly-in-string
            .replace("${ID}", String(props.item.id))
            // eslint-disable-next-line no-template-curly-in-string
            .replace("${TITLE}", props.item.title)
            // eslint-disable-next-line no-template-curly-in-string
            .replace("${VERSION_NAME}", props.version.title)
            // eslint-disable-next-line no-template-curly-in-string
            .replace("${STATUS}", props.item.status),
        props.item.file_properties.file
    );
});
</script>

<script lang="ts">
import { defineComponent } from "@vue/composition-api";

export default defineComponent({});
</script>
