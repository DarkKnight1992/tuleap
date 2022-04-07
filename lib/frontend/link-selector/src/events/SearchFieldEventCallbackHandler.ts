/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { LinkSelectorSearchFieldCallback } from "../type";

const TRIGGER_CALLBACK_DELAY_IN_MS = 250;

export const SearchFieldEventCallbackHandler = {
    init: (
        search_field_element: HTMLInputElement,
        callback: LinkSelectorSearchFieldCallback
    ): void => {
        let timeout_id: number | undefined;
        search_field_element.addEventListener("input", () => {
            clearTimeout(timeout_id);

            timeout_id = setTimeout(() => {
                callback(search_field_element.value);
            }, TRIGGER_CALLBACK_DELAY_IN_MS);
        });
    },
};
