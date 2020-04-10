/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

function assertFeedbackContainsMessage(expected_feedback_message) {
    cy.get("[data-test=feedback]").contains(expected_feedback_message);
}

describe("User preferences", () => {
    before(() => {
        cy.clearCookie("__Host-TULEAP_session_hash");
        cy.heisenbergLogin();
    });

    beforeEach(function () {
        Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
        cy.visit("/account/");
    });

    describe("User is able to", () => {
        it("Change his name", () => {
            cy.get("#realname").clear().type("Heisenberg");
            cy.get("[data-test=user-prefs-submit-button]").click();

            cy.get("#realname").should("have.value", "Heisenberg");
            assertFeedbackContainsMessage("Real name successfully updated");
        });

        it("Change his email address", () => {
            cy.get("#email").clear().type("heisenberg@vamonos-pest.us");
            cy.get("[data-test=user-prefs-submit-button]").click();

            cy.get("[data-test=user-prefs-email-need-confirmation-warning]").contains(
                "An email change was requested, please check your inbox to complete the change."
            );
            assertFeedbackContainsMessage(
                "New email was successfully saved. To complete the change, please click on the confirmation link you will receive by email (new address)."
            );
        });

        it("Change his timezone", () => {
            cy.get("#timezone").select("America/Denver", { force: true });
            cy.get("[data-test=user-prefs-submit-button]").click();

            cy.get("#timezone").should("have.value", "America/Denver");
            assertFeedbackContainsMessage("Timezone successfully updated");
        });

        it("Change his avatar", () => {
            cy.get("#account-information-avatar-button").click();
            cy.get("#account-information-avatar-modal-select-file")
                .uploadFixtureFile("heisenberg.jpg", "image/jpg")
                .trigger("change", { force: true });

            cy.get("[data-test=user-prefs-save-avatar-button]").click();
            assertFeedbackContainsMessage("Avatar changed!");
        });

        it("Change his current avatar for the default one", () => {
            cy.get("#account-information-avatar-button").click();
            cy.get("#account-information-avatar-modal-use-default-button").click();
            cy.get("[data-test=user-prefs-save-avatar-button]").click();

            assertFeedbackContainsMessage("Avatar changed!");
        });
    });
});
