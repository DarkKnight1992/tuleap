<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <h2>
        Baseline #{{ baseline.id }} - {{ baseline.name }}
        <span class="baseline-label-author tlp-text-muted">
            <span v-translate>Created by</span>
            <user-badge v-bind:user="author" class="baseline-label-author-badge" />
            <span v-translate>on</span>
            {{ humanized_snapshot_date }}
        </span>
    </h2>
</template>

<script>
import UserBadge from "./UserBadge.vue";
import { mapGetters } from "vuex";
import DateUtils from "../../support/date-utils";

export default {
    name: "BaselineLabel",
    components: { UserBadge },
    props: {
        baseline: { required: true, type: Object },
    },
    computed: {
        ...mapGetters(["findUserById"]),
        author() {
            return this.findUserById(this.baseline.author_id);
        },
        humanized_snapshot_date() {
            return DateUtils.humanFormat(this.baseline.snapshot_date);
        },
    },
};
</script>
