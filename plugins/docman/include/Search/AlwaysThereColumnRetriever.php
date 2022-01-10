<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\Search;

use Docman_SettingsBo;

final class AlwaysThereColumnRetriever
{
    public function __construct(private Docman_SettingsBo $docman_settings)
    {
    }

    public function getColumns(): array
    {
        $useStatus = $this->docman_settings->getMetadataUsage('status');

        if ($useStatus) {
            return ['status', 'title', 'description', 'location', 'owner', 'update_date'];
        } else {
            return ['title', 'description', 'location', 'owner', 'update_date'];
        }
    }
}
