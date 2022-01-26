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

namespace Tuleap\Docman\REST\v1\Folders;

use Docman_ItemFactory;
use Tuleap\Docman\REST\v1\ItemRepresentationCollectionBuilder;
use Tuleap\Docman\REST\v1\ItemRepresentationVisitor;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class BuildSearchedItemRepresentationsFromSearchReportTest extends TestCase
{
    use GlobalLanguageMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\UserManager
     */
    private $user_manager;
    /**
     * @var Docman_ItemFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $item_factory;
    private BuildSearchedItemRepresentationsFromSearchReport $representation_builder;
    private ItemStatusMapper $status_mapper;

    protected function setUp(): void
    {
        $docman_settings = $this->createMock(\Docman_SettingsBo::class);
        $docman_settings->method('getMetadataUsage')->with('status')->willReturn("1");
        $item_dao            = $this->createMock(\Docman_ItemDao::class);
        $this->status_mapper = new ItemStatusMapper($docman_settings);
        $this->user_manager  = $this->createMock(\UserManager::class);
        $permissions_manager = $this->createMock(\Docman_PermissionsManager::class);

        $this->item_factory           = $this->createMock(Docman_ItemFactory::class);
        $this->representation_builder = new BuildSearchedItemRepresentationsFromSearchReport(
            $this->status_mapper,
            $this->user_manager,
            new ItemRepresentationCollectionBuilder($this->item_factory, $permissions_manager, $this->createMock(ItemRepresentationVisitor::class), $item_dao),
            $this->item_factory
        );
    }

    public function testItBuildsItemRepresentations(): void
    {
        $report = new \Docman_Report();
        $folder = new \Docman_Folder(["group_id" => 101]);

        $item_one_array = [
            "item_id"     => 1,
            "title"       => "folder",
            "description" => "",
            "update_date" => "123456789",
            "status"      => PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
            "user_id"     => 101,
            "parent_id"   => 0,
        ];
        $item_one       = new \Docman_Folder($item_one_array);
        $item_two_array = [
            "item_id"     => 2,
            "title"       => "file",
            "description" => "",
            "update_date" => "987654321",
            "status"      => PLUGIN_DOCMAN_ITEM_STATUS_REJECTED,
            "user_id"     => 101,
            "parent_id"   => 0,
        ];
        $item_two       = new \Docman_File($item_two_array);

        $this->item_factory->method('getItemList')->willReturn(
            new \ArrayIterator([$item_one, $item_two])
        );
        $this->user_manager->method('getUserById')->willReturn(UserTestBuilder::buildWithId(101));

        $collection = $this->representation_builder->build($report, $folder, UserTestBuilder::aUser()->build(), 50, 0);

        $this->assertItemEqualsRepresentation($item_one_array, $collection->search_representations[0]);
        $this->assertItemEqualsRepresentation($item_two_array, $collection->search_representations[1]);
        $this->assertEquals("folder", $collection->search_representations[0]->type);
        $this->assertEquals("file", $collection->search_representations[1]->type);

        self::assertCount(2, $collection->search_representations);
    }

    private function assertItemEqualsRepresentation(array $item, SearchRepresentation $representation): void
    {
        self::assertSame($item['item_id'], $representation->id);
        self::assertSame($item['title'], $representation->title);
        self::assertSame($item['description'], $representation->post_processed_description);
        self::assertSame($this->status_mapper->getItemStatusFromItemStatusNumber($item['status']), $representation->status);
        self::assertSame($item['user_id'], $representation->owner->id);
    }
}
