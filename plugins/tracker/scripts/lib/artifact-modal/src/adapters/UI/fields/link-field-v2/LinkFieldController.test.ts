/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { LinkedArtifactCollectionPresenter } from "./LinkedArtifactCollectionPresenter";
import type { LinkFieldControllerType } from "./LinkFieldController";
import { LinkFieldController } from "./LinkFieldController";
import { RetrieveAllLinkedArtifactsStub } from "../../../../../tests/stubs/RetrieveAllLinkedArtifactsStub";
import type { RetrieveAllLinkedArtifacts } from "../../../../domain/fields/link-field-v2/RetrieveAllLinkedArtifacts";
import { CurrentArtifactIdentifierStub } from "../../../../../tests/stubs/CurrentArtifactIdentifierStub";
import { Fault } from "@tuleap/fault";
import { NoLinksInCreationModeFault } from "../../../../domain/fields/link-field-v2/NoLinksInCreationModeFault";
import { RetrieveLinkedArtifactsSyncStub } from "../../../../../tests/stubs/RetrieveLinkedArtifactsSyncStub";
import { AddLinkMarkedForRemovalStub } from "../../../../../tests/stubs/AddLinkMarkedForRemovalStub";
import { DeleteLinkMarkedForRemovalStub } from "../../../../../tests/stubs/DeleteLinkMarkedForRemovalStub";
import { VerifyLinkIsMarkedForRemovalStub } from "../../../../../tests/stubs/VerifyLinkIsMarkedForRemovalStub";
import { LinkedArtifactStub } from "../../../../../tests/stubs/LinkedArtifactStub";
import { LinkedArtifactIdentifierStub } from "../../../../../tests/stubs/LinkedArtifactIdentifierStub";
import { NotifyFaultStub } from "../../../../../tests/stubs/NotifyFaultStub";
import { ArtifactCrossReferenceStub } from "../../../../../tests/stubs/ArtifactCrossReferenceStub";
import type { LinkFieldPresenter } from "./LinkFieldPresenter";
import { ArtifactLinkSelectorAutoCompleter } from "./ArtifactLinkSelectorAutoCompleter";
import { RetrieveMatchingArtifactStub } from "../../../../../tests/stubs/RetrieveMatchingArtifactStub";
import { LinkableArtifactStub } from "../../../../../tests/stubs/LinkableArtifactStub";
import type { LinkAdditionPresenter } from "./LinkAdditionPresenter";
import type { LinkableArtifact } from "../../../../domain/fields/link-field-v2/LinkableArtifact";
import type { NewLinkCollectionPresenter } from "./NewLinkCollectionPresenter";
import { AddNewLinkStub } from "../../../../../tests/stubs/AddNewLinkStub";
import { RetrieveNewLinksStub } from "../../../../../tests/stubs/RetrieveNewLinksStub";
import { LinkTypeStub } from "../../../../../tests/stubs/LinkTypeStub";
import { IS_CHILD_LINK_TYPE } from "@tuleap/plugin-tracker-constants/src/constants";
import { ClearFaultNotificationStub } from "../../../../../tests/stubs/ClearFaultNotificationStub";
import type { RetrieveLinkedArtifactsSync } from "../../../../domain/fields/link-field-v2/RetrieveLinkedArtifactsSync";
import type { VerifyLinkIsMarkedForRemoval } from "../../../../domain/fields/link-field-v2/VerifyLinkIsMarkedForRemoval";
import type { RetrieveNewLinks } from "../../../../domain/fields/link-field-v2/RetrieveNewLinks";
import { DeleteNewLinkStub } from "../../../../../tests/stubs/DeleteNewLinkStub";
import { NewLinkStub } from "../../../../../tests/stubs/NewLinkStub";

const ARTIFACT_ID = 60;
const FIELD_ID = 714;

describe(`LinkFieldController`, () => {
    let links_retriever: RetrieveAllLinkedArtifacts,
        links_retriever_sync: RetrieveLinkedArtifactsSync,
        deleted_link_adder: AddLinkMarkedForRemovalStub,
        deleted_link_remover: DeleteLinkMarkedForRemovalStub,
        deleted_link_verifier: VerifyLinkIsMarkedForRemoval,
        fault_notifier: NotifyFaultStub,
        new_link_adder: AddNewLinkStub,
        new_links_retriever: RetrieveNewLinks,
        new_link_remover: DeleteNewLinkStub;

    beforeEach(() => {
        links_retriever = RetrieveAllLinkedArtifactsStub.withoutLink();
        links_retriever_sync = RetrieveLinkedArtifactsSyncStub.withoutLink();
        deleted_link_adder = AddLinkMarkedForRemovalStub.withCount();
        deleted_link_remover = DeleteLinkMarkedForRemovalStub.withCount();
        deleted_link_verifier = VerifyLinkIsMarkedForRemovalStub.withNoLinkMarkedForRemoval();
        fault_notifier = NotifyFaultStub.withCount();
        new_link_adder = AddNewLinkStub.withCount();
        new_links_retriever = RetrieveNewLinksStub.withoutLink();
        new_link_remover = DeleteNewLinkStub.withCount();
    });

    const getController = (): LinkFieldControllerType => {
        const current_artifact_identifier = CurrentArtifactIdentifierStub.withId(18);
        const cross_reference = ArtifactCrossReferenceStub.withRef("story #18");
        return LinkFieldController(
            links_retriever,
            links_retriever_sync,
            deleted_link_adder,
            deleted_link_remover,
            deleted_link_verifier,
            fault_notifier,
            ArtifactLinkSelectorAutoCompleter(
                RetrieveMatchingArtifactStub.withMatchingArtifact(
                    LinkableArtifactStub.withDefaults()
                ),
                fault_notifier,
                ClearFaultNotificationStub.withCount(),
                current_artifact_identifier
            ),
            new_link_adder,
            new_link_remover,
            new_links_retriever,
            {
                field_id: FIELD_ID,
                type: "art_link",
                label: "Artifact link",
                allowed_types: [],
            },
            current_artifact_identifier,
            cross_reference
        );
    };

    describe(`displayField()`, () => {
        const displayField = (): LinkFieldPresenter => getController().displayField();

        it(`returns a presenter for the field and current artifact cross reference`, () => {
            const presenter = displayField();
            expect(presenter.field_id).toBe(FIELD_ID);
        });
    });

    describe(`displayLinkedArtifacts()`, () => {
        const displayLinkedArtifacts = (): Promise<LinkedArtifactCollectionPresenter> =>
            getController().displayLinkedArtifacts();

        it(`when the modal is in creation mode,
            it won't notify that there has been a fault
            and it will return an empty presenter`, async () => {
            links_retriever = RetrieveAllLinkedArtifactsStub.withFault(
                NoLinksInCreationModeFault()
            );
            const presenter = await displayLinkedArtifacts();

            expect(presenter.has_loaded_content).toBe(true);
            expect(fault_notifier.getCallCount()).toBe(0);
        });

        it(`when the modal is in edition mode and it succeeds loading,
            it will return a presenter with the linked artifacts`, async () => {
            const linked_artifact = LinkedArtifactStub.withDefaults();
            links_retriever = RetrieveAllLinkedArtifactsStub.withLinkedArtifacts(linked_artifact);
            const presenter = await displayLinkedArtifacts();

            expect(presenter.has_loaded_content).toBe(true);
        });

        it(`when the modal is in edition mode and it fails loading,
            it will notify that there has been a fault
            and it will return an empty presenter`, async () => {
            links_retriever = RetrieveAllLinkedArtifactsStub.withFault(Fault.fromMessage("Ooops"));
            const presenter = await displayLinkedArtifacts();

            expect(presenter.has_loaded_content).toBe(true);
            expect(fault_notifier.getCallCount()).toBe(1);
        });
    });

    describe(`markForRemoval`, () => {
        const markForRemoval = (): LinkedArtifactCollectionPresenter => {
            const identifier = LinkedArtifactIdentifierStub.withId(ARTIFACT_ID);
            const linked_artifact = LinkedArtifactStub.withDefaults({ identifier });
            links_retriever_sync =
                RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(linked_artifact);
            deleted_link_verifier = VerifyLinkIsMarkedForRemovalStub.withAllLinksMarkedForRemoval();
            return getController().markForRemoval(identifier);
        };

        it(`stores the given identifier as a link marked for removal and returns an updated presenter`, () => {
            const presenter = markForRemoval();

            expect(deleted_link_adder.getCallCount()).toBe(1);
            const is_marked = presenter.linked_artifacts.some(
                (linked_artifact) => linked_artifact.is_marked_for_removal
            );
            expect(is_marked).toBe(true);
        });
    });

    describe(`unmarkForRemoval`, () => {
        const unmarkForRemoval = (): LinkedArtifactCollectionPresenter => {
            const identifier = LinkedArtifactIdentifierStub.withId(ARTIFACT_ID);
            const linked_artifact = LinkedArtifactStub.withDefaults({ identifier });
            links_retriever_sync =
                RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(linked_artifact);
            return getController().unmarkForRemoval(identifier);
        };

        it(`deletes the given identifier in the stored links marked for removal,
            and returns an updated presenter`, () => {
            const presenter = unmarkForRemoval();

            expect(deleted_link_remover.getCallCount()).toBe(1);
            const is_marked = presenter.linked_artifacts.some(
                (linked_artifact) => linked_artifact.is_marked_for_removal
            );
            expect(is_marked).toBe(false);
        });
    });

    describe(`onLinkableArtifactSelection`, () => {
        const onSelection = (artifact: LinkableArtifact | null): LinkAdditionPresenter =>
            getController().onLinkableArtifactSelection(artifact);

        it(`when selection is null, it will return a presenter with disabled button`, () => {
            const presenter = onSelection(null);
            expect(presenter.is_add_button_disabled).toBe(true);
        });

        it(`when an artifact is selected, it will return a presenter with enabled button`, () => {
            const presenter = onSelection(LinkableArtifactStub.withDefaults());
            expect(presenter.is_add_button_disabled).toBe(false);
        });
    });

    describe(`addNewLink`, () => {
        const addNewLink = (): NewLinkCollectionPresenter => {
            const linkable_artifact = LinkableArtifactStub.withDefaults({
                id: ARTIFACT_ID,
            });
            const link_type = LinkTypeStub.buildChildLinkType();
            new_links_retriever = RetrieveNewLinksStub.withNewLinks(
                NewLinkStub.withIdAndType(ARTIFACT_ID, link_type)
            );
            return getController().addNewLink(linkable_artifact, link_type);
        };

        it(`adds a new link to the stored new links and returns an updated presenter`, () => {
            const presenter = addNewLink();

            expect(new_link_adder.getCallCount()).toBe(1);
            expect(presenter.links).toHaveLength(1);
            expect(presenter.links[0].identifier.id).toBe(ARTIFACT_ID);
            expect(presenter.links[0].link_type.shortname).toBe(IS_CHILD_LINK_TYPE);
        });
    });

    describe(`removeNewLink`, () => {
        const removeNewLink = (): NewLinkCollectionPresenter => {
            const new_link = NewLinkStub.withDefaults();
            new_links_retriever = RetrieveNewLinksStub.withoutLink();
            return getController().removeNewLink(new_link);
        };

        it(`deletes a new link and returns an updated presenter`, () => {
            const presenter = removeNewLink();

            expect(new_link_remover.getCallCount()).toBe(1);
            expect(presenter.links).toHaveLength(0);
        });
    });
});
