/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { shallowMount } from "@vue/test-utils";
import TimePeriodControl from "./TimePeriodControl.vue";
import { createRoadmapLocalVue } from "../../../helpers/local-vue-for-test";

describe("TimePeriodControl", () => {
    it("Emits input event when the value is changed", async () => {
        const wrapper = shallowMount(TimePeriodControl, {
            propsData: {
                value: "month",
            },
            localVue: await createRoadmapLocalVue(),
        });

        const options = wrapper.find("[data-test=select-timescale]").findAll("option");
        options.at(2).setSelected();
        options.at(1).setSelected();
        options.at(0).setSelected();

        const input_event = wrapper.emitted("input");
        if (!input_event) {
            throw new Error("Failed to catch input event");
        }

        expect(input_event[0][0]).toBe("quarter");
        expect(input_event[1][0]).toBe("month");
        expect(input_event[2][0]).toBe("week");
    });
});
