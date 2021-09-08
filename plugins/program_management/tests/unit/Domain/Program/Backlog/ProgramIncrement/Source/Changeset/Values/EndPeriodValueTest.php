<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldReferences;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveEndPeriodValueStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerIdentifierStub;

final class EndPeriodValueTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const VALUE = '2023-09-01';

    public function testItBuildsFromSynchronizedFields(): void
    {
        $value = EndPeriodValue::fromSynchronizedFields(
            RetrieveEndPeriodValueStub::withValue(self::VALUE),
            SynchronizedFieldReferences::fromTrackerIdentifier(GatherSynchronizedFieldsStub::withDefaults(), TrackerIdentifierStub::buildWithDefault())
        );
        self::assertSame(self::VALUE, $value->getValue());
    }
}
