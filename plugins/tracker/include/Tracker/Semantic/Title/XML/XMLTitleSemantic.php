<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic\Title\XML;

use Tracker_Semantic_Title;
use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;
use Tuleap\Tracker\FormElement\XML\XMLReference;
use Tuleap\Tracker\Semantic\XML\XMLSemantic;

final class XMLTitleSemantic extends XMLSemantic
{
    /**
     * @var XMLReference
     * @readonly
     */
    private $reference;

    public function __construct(XMLReference $reference)
    {
        parent::__construct(Tracker_Semantic_Title::NAME);
        $this->reference = $reference;
    }

    public function export(\SimpleXMLElement $parent_node, XMLFormElementFlattenedCollection $form_elements): \SimpleXMLElement
    {
        $child = parent::export($parent_node, $form_elements);

        $cdata = new \XML_SimpleXMLCDATAFactory();
        $cdata->insert($child, 'shortname', Tracker_Semantic_Title::NAME);
        $cdata->insert($child, 'label', dgettext('tuleap-tracker', 'Title'));
        $cdata->insert($child, 'description', dgettext('tuleap-tracker', 'Define the title of an artifact'));
        $child->addChild('field')->addAttribute('REF', $this->reference->getId($form_elements));

        return $child;
    }
}
