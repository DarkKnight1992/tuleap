/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { sendNotifications } from "./send-notifications";

import * as spinner from "./spinner-activation";
import * as feedback from "./feedback-display";
import * as error from "./handle-error";
import * as tlp from "../../themes/tlp/src/js/fetch-wrapper";
import { mockFetchError, mockFetchSuccess } from "../../themes/tlp/mocks/tlp-fetch-mock-helper";

jest.mock("./spinner-activation");
jest.mock("./feedback-display");
jest.mock("./handle-error");
jest.mock("tlp");

describe("send-notifications", () => {
    describe("sendNotifactions", () => {
        let form: HTMLFormElement;
        let email_field: HTMLInputElement;

        beforeEach(() => {
            const doc = document.implementation.createHTMLDocument();

            email_field = doc.createElement("input");
            email_field.type = "email";
            email_field.name = "invite_buddies_email";

            form = doc.createElement("form");
            form.appendChild(email_field);

            doc.body.appendChild(form);

            jest.clearAllMocks();
        });

        it("Creates invitation and displays success feedback", async () => {
            email_field.value = "peter@example.com, wendy@example.com";

            const activateSpinner = jest.spyOn(spinner, "activateSpinner");
            const deactivateSpinner = jest.spyOn(spinner, "deactivateSpinner");
            const displaySuccess = jest.spyOn(feedback, "displaySuccess");
            const handleError = jest.spyOn(error, "handleError");

            const tlpPostMock = jest.spyOn(tlp, "post");
            const response_body = { failures: [] };
            mockFetchSuccess(tlpPostMock, { return_json: response_body });

            await sendNotifications(form);
            expect(tlpPostMock).toHaveBeenCalledWith(`/api/v1/invitations`, {
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    emails: ["peter@example.com", "wendy@example.com"],
                }),
            });

            expect(activateSpinner).toHaveBeenCalled();
            expect(displaySuccess).toHaveBeenCalledWith(
                ["peter@example.com", "wendy@example.com"],
                response_body
            );
            expect(handleError).not.toHaveBeenCalled();
            expect(deactivateSpinner).toHaveBeenCalled();
        });

        it("Tries to create invitation and displays error", async () => {
            email_field.value = "peter@example.com, wendy@example.com";

            const activateSpinner = jest.spyOn(spinner, "activateSpinner");
            const deactivateSpinner = jest.spyOn(spinner, "deactivateSpinner");
            const displaySuccess = jest.spyOn(feedback, "displaySuccess");
            const handleError = jest.spyOn(error, "handleError");

            const tlpPostMock = jest.spyOn(tlp, "post");
            mockFetchError(tlpPostMock, {
                error_json: {
                    error: {
                        code: 403,
                        message: "Forbidden",
                    },
                },
            });

            await sendNotifications(form);
            expect(tlpPostMock).toHaveBeenCalledWith(`/api/v1/invitations`, {
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    emails: ["peter@example.com", "wendy@example.com"],
                }),
            });

            expect(activateSpinner).toHaveBeenCalled();
            expect(displaySuccess).not.toHaveBeenCalled();
            expect(handleError).toHaveBeenCalled();
            expect(deactivateSpinner).toHaveBeenCalled();
        });
    });
});
