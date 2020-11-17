<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Team\Creation;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ScaledAgile\Program\Plan\BuildProgram;
use Tuleap\ScaledAgile\Program\Program;
use Tuleap\Test\Builders\UserTestBuilder;

final class TeamCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItCreatesAPlan(): void
    {
        $program_adapter = \Mockery::mock(BuildProgram::class);
        $team_adapter    = \Mockery::mock(BuildTeam::class);

        $project_id      = 101;
        $team_project_id = 2;

        $user = UserTestBuilder::aUser()->build();

        $program = new Program($project_id);
        $program_adapter->shouldReceive('buildProgramProject')
            ->with($project_id, $user)->once()
            ->andReturn($program);
        $collection = new TeamCollection([new Team($team_project_id)], $program);
        $team_adapter->shouldReceive('buildTeamProject')
            ->with(
                [$team_project_id],
                $program,
                $user
            )->once()
            ->andReturn($collection);

        $team_dao = \Mockery::mock(TeamStore::class);
        $team_dao->shouldReceive('save')->with($collection)->once();

        $team_adapter = new TeamCreator($program_adapter, $team_adapter, $team_dao);
        $team_adapter->create($user, $project_id, [$team_project_id]);
    }
}
