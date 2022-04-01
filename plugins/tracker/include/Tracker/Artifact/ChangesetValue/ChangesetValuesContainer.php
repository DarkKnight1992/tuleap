<?php
/**
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

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\ChangesetValue;

use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValueFormatter;

/**
 * I hold the new values for all modified fields, for a new changeset of an artifact.
 * Artifact Links field has special treatment so that we can handle the "reverse" artifact links separately.
 * We don't want them in $fields_data.
 * @psalm-immutable
 */
final class ChangesetValuesContainer
{
    public function __construct(private array $fields_data, private ?NewArtifactLinkChangesetValue $artifact_links)
    {
        if ($artifact_links !== null) {
            // We must still add forward artifact links to $fields_data so that it can be saved and processed like normal
            $this->fields_data[$artifact_links->getFieldId()] = NewArtifactLinkChangesetValueFormatter::formatForWebUI(
                $artifact_links
            );
        }
    }

    public function getFieldsData(): array
    {
        return $this->fields_data;
    }

    public function getArtifactLinkValue(): ?NewArtifactLinkChangesetValue
    {
        return $this->artifact_links;
    }
}
