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
    <div class="document-header document-header-search">
        <h1 class="document-header-title">
            {{ title }}
        </h1>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";
import { ref } from "@vue/composition-api";

const { $gettext } = useGettext();

const title = ref($gettext("Search"));
</script>

<script lang="ts">
import { defineComponent } from "@vue/composition-api";

export default defineComponent({});
</script>
