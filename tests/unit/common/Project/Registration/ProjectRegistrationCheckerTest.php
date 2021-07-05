<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\Registration;

use ForgeConfig;
use ProjectCreationData;
use Psr\Log\NullLogger;
use Rule_ProjectFullName;
use Rule_ProjectName;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

class ProjectRegistrationCheckerTest extends TestCase
{
    use ForgeConfigSandbox;

    private ProjectRegistrationChecker $checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProjectRegistrationUserPermissionChecker
     */
    private $permission_checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Rule_ProjectName
     */
    private $rule_project_name;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Rule_ProjectFullName
     */
    private $rule_project_full_name;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permission_checker     = $this->createMock(ProjectRegistrationUserPermissionChecker::class);
        $this->rule_project_name      = $this->createMock(Rule_ProjectName::class);
        $this->rule_project_full_name = $this->createMock(Rule_ProjectFullName::class);

        $this->checker = new ProjectRegistrationChecker(
            $this->permission_checker,
            $this->rule_project_name,
            $this->rule_project_full_name
        );
    }

    public function testItStopCollectingIfThereIsALeastAPermissionError(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->permission_checker
            ->expects(self::once())
            ->method('checkUserCreateAProject')
            ->with($user)
            ->willThrowException(
                new class extends RegistrationForbiddenException
                {
                    public function getI18NMessage(): string
                    {
                        return '';
                    }
                }
            );

        $data = new ProjectCreationData(
            new DefaultProjectVisibilityRetriever(),
            new NullLogger()
        );

        $data->setUnixName("not:val_id");
        $data->setFullName("Not vaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaalid");

        $this->rule_project_name
            ->expects(self::never())
            ->method('isValid');

        $this->rule_project_full_name
            ->expects(self::never())
            ->method('isValid');

        $errors_collection = $this->checker->collectAllErrorsForProjectRegistration(
            $user,
            $data
        );

        self::assertNotEmpty($errors_collection->getErrors());
        self::assertCount(1, $errors_collection->getErrors());
        self::assertInstanceOf(RegistrationForbiddenException::class, $errors_collection->getErrors()[0]);
    }

    public function testItCollectsAllDataErrorsIfThereIsNoPermissionError(): void
    {
        ForgeConfig::set("enable_not_mandatory_description", 0);

        $user = UserTestBuilder::aUser()->build();

        $this->permission_checker
            ->expects(self::once())
            ->method('checkUserCreateAProject')
            ->with($user);

        $data = new ProjectCreationData(
            new DefaultProjectVisibilityRetriever(),
            new NullLogger()
        );

        $data->setUnixName("not:val_id");
        $data->setFullName("Not vaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaalid");

        $this->rule_project_name
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(false);

        $this->rule_project_name
            ->expects(self::once())
            ->method('getErrorMessage')
            ->willReturn('error 01');

        $this->rule_project_full_name
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(false);

        $this->rule_project_full_name
            ->expects(self::once())
            ->method('getErrorMessage')
            ->willReturn('error 02');

        $errors_collection = $this->checker->collectAllErrorsForProjectRegistration(
            $user,
            $data
        );

        self::assertNotEmpty($errors_collection->getErrors());
        self::assertCount(3, $errors_collection->getErrors());
        self::assertInstanceOf(ProjectInvalidShortNameException::class, $errors_collection->getErrors()[0]);
        self::assertInstanceOf(ProjectInvalidFullNameException::class, $errors_collection->getErrors()[1]);
        self::assertInstanceOf(ProjectDescriptionMandatoryException::class, $errors_collection->getErrors()[2]);
    }

    public function testItCollectionIsEmptyIfAllIsOK(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->permission_checker
            ->expects(self::once())
            ->method('checkUserCreateAProject')
            ->with($user);

        $data = new ProjectCreationData(
            new DefaultProjectVisibilityRetriever(),
            new NullLogger()
        );

        $data->setUnixName("shortnale");
        $data->setFullName("Long Name");
        $data->setShortDescription("Description");

        $this->rule_project_name
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $this->rule_project_full_name
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $errors_collection = $this->checker->collectAllErrorsForProjectRegistration(
            $user,
            $data
        );

        self::assertEmpty($errors_collection->getErrors());
    }

    public function testItCollectsPermissionsErrors(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->permission_checker
            ->expects(self::once())
            ->method('checkUserCreateAProject')
            ->with($user)
            ->willThrowException(
                new class extends RegistrationForbiddenException
                {
                    public function getI18NMessage(): string
                    {
                        return '';
                    }
                }
            );

        $errors_collection = $this->checker->collectPermissionErrorsForProjectRegistration(
            $user
        );

        self::assertNotEmpty($errors_collection->getErrors());
        self::assertCount(1, $errors_collection->getErrors());
        self::assertInstanceOf(RegistrationForbiddenException::class, $errors_collection->getErrors()[0]);
    }

    public function testCollectionIsEmptyIfThereIsNoPermissionsErrors(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->permission_checker
            ->expects(self::once())
            ->method('checkUserCreateAProject')
            ->with($user);

        $errors_collection = $this->checker->collectPermissionErrorsForProjectRegistration(
            $user
        );

        self::assertEmpty($errors_collection->getErrors());
    }
}
