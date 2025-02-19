<!--
  - Copyright (c) 2021-Present Enalean
  -
  - Permission is hereby granted, free of charge, to any person obtaining a copy
  - of this software and associated documentation files (the "Software"), to deal
  - in the Software without restriction, including without limitation the rights
  - to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
  - copies of the Software, and to permit persons to whom the Software is
  - furnished to do so, subject to the following conditions:
  -
  - The above copyright notice and this permission notice shall be included in all
  - copies or substantial portions of the Software.
  -
  - THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  - IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  - FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
  - AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  - LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  - OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
  - SOFTWARE.
  -->

<template>
    <a
        v-bind:href="sanitized_href"
        v-bind:aria-label="label"
        class="project-sidebar-nav-item"
        v-bind:class="{ active: is_active }"
        v-bind:title="description"
        v-bind:target="open_in_new_tab ? '_blank' : '_self'"
        v-bind:rel="open_in_new_tab ? 'noopener noreferrer' : ''"
        v-bind:data-shortcut-sidebar="shortcut"
        data-test="project-sidebar-tool"
    >
        <i
            class="project-sidebar-nav-item-icon"
            aria-hidden="true"
            v-bind:class="icon"
            data-test="tool-icon"
        ></i>
        <span class="project-sidebar-nav-item-label">{{ label }}</span>
        <i
            v-if="open_in_new_tab"
            class="fas fa-arrow-right project-sidebar-nav-item-new-tab"
            aria-hidden="true"
            data-test="tool-new-tab-icon"
        ></i>
    </a>
</template>
<script setup lang="ts">
import { computed } from "vue";
import { sanitizeURL } from "../url-sanitizer";

// We cannot directly import the Tool interface from the external file so we duplicate the content for now
// See https://github.com/vuejs/vue-next/issues/4294
const props = defineProps<{
    href: string;
    label: string;
    description: string;
    icon: string;
    open_in_new_tab: boolean;
    is_active: boolean;
    shortcut_id: string;
}>();
const sanitized_href = computed(() => sanitizeURL(props.href));
const shortcut = computed(() => `sidebar-${props.shortcut_id}`);
</script>
