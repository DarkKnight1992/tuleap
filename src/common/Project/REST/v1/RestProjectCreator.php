<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Project\REST\v1;

use Luracast\Restler\RestException;
use Project;
use ProjectCreationData;
use Tuleap\Project\Admin\Categories\CategoryCollection;
use Tuleap\Project\Admin\Categories\ProjectCategoriesException;
use Tuleap\Project\Admin\Categories\ProjectCategoriesUpdater;
use Tuleap\Project\Admin\DescriptionFields\FieldDoesNotExistException;
use Tuleap\Project\Admin\DescriptionFields\FieldUpdator;
use Tuleap\Project\Admin\DescriptionFields\MissingMandatoryFieldException;
use Tuleap\Project\Registration\MaxNumberOfProjectReachedForPlatformException;
use Tuleap\Project\Registration\MaxNumberOfProjectReachedForUserException;
use Tuleap\Project\Registration\ProjectDescriptionMandatoryException;
use Tuleap\Project\Registration\ProjectInvalidFullNameException;
use Tuleap\Project\Registration\ProjectInvalidShortNameException;
use Tuleap\Project\Registration\RegistrationForbiddenException;
use Tuleap\Project\Registration\Template\InvalidTemplateException;
use Tuleap\Project\Registration\Template\InvalidXMLTemplateNameException;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\SystemEventRunnerForProjectCreationFromXMLTemplate;
use Tuleap\Project\XML\Import\DirectoryArchive;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Project\XML\Import\ImportNotValidException;

class RestProjectCreator
{
    /**
     * @var \ProjectCreator
     */
    private $project_creator;
    /**
     * @var \ProjectXMLImporter
     */
    private $project_XML_importer;
    /**
     * @var TemplateFactory
     */
    private $template_factory;
    /**
     * @var ProjectCategoriesUpdater
     */
    private $categories_updater;
    /**
     * @var FieldUpdator
     */
    private $fields_updater;

    public function __construct(
        \ProjectCreator $project_creator,
        \ProjectXMLImporter $project_XML_importer,
        TemplateFactory $template_factory,
        ProjectCategoriesUpdater $categories_updater,
        FieldUpdator $fields_updater
    ) {
        $this->project_creator      = $project_creator;
        $this->project_XML_importer = $project_XML_importer;
        $this->template_factory     = $template_factory;
        $this->categories_updater   = $categories_updater;
        $this->fields_updater       = $fields_updater;
    }

    /**
     * @throws RestException
     * @throws \Project_Creation_Exception
     * @throws ProjectInvalidFullNameException
     * @throws ProjectInvalidShortNameException
     * @throws ProjectDescriptionMandatoryException
     * @throws InvalidTemplateException
     */
    public function create(
        ProjectPostRepresentation $post_representation,
        ProjectCreationData $creation_data
    ): Project {
        try {
            $category_collection = $this->getCategoryCollection($post_representation);
            $this->categories_updater->checkCollectionConsistency($category_collection);

            $field_collection = $this->getFieldCollection($post_representation);
            $this->fields_updater->checkFieldConsistency($field_collection);

            $project = $this->createProjectWithSelectedTemplate($post_representation, $creation_data);
            $this->categories_updater->update($project, $category_collection);
            $this->fields_updater->updateFromArray($field_collection, $project);
            return $project;
        } catch (MaxNumberOfProjectReachedForPlatformException | MaxNumberOfProjectReachedForUserException $exception) {
            throw new RestException(429, $exception->getMessage());
        } catch (RegistrationForbiddenException $exception) {
            throw new RestException(403, $exception->getMessage());
        } catch (ProjectCategoriesException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (FieldDoesNotExistException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (MissingMandatoryFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (ImportNotValidException $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    /**
     * @throws ImportNotValidException
     * @throws InvalidTemplateException
     * @throws \Project_Creation_Exception
     * @throws ProjectInvalidFullNameException
     * @throws ProjectInvalidShortNameException
     * @throws ProjectDescriptionMandatoryException
     */
    private function createProjectWithSelectedTemplate(
        ProjectPostRepresentation $post_representation,
        ProjectCreationData $creation_data
    ): Project {
        if ($post_representation->template_id !== null) {
            return $this->project_creator->processProjectCreation(
                $creation_data
            );
        }

        if ($post_representation->xml_template_name !== null) {
            $template = $this->template_factory->getTemplate($post_representation->xml_template_name);
            $xml_path = $template->getXMLPath();

            $archive = new DirectoryArchive(dirname($xml_path));

            return $this->template_factory->recordUsedTemplate(
                $this->project_XML_importer->importWithProjectData(
                    new ImportConfig(),
                    $archive,
                    new SystemEventRunnerForProjectCreationFromXMLTemplate(),
                    $creation_data
                ),
                $template,
            );
        }

        throw new InvalidXMLTemplateNameException();
    }

    private function getCategoryCollection(ProjectPostRepresentation $project_post_representation): CategoryCollection
    {
        $categories = [];
        if ($project_post_representation->categories !== null) {
            foreach ($project_post_representation->categories as $category_representation) {
                $category = new \TroveCat($category_representation->category_id, '', '');
                $category->addChildren(new \TroveCat($category_representation->value_id, '', ''));
                $categories[] = $category;
            }
        }
        return new CategoryCollection(...$categories);
    }

    private function getFieldCollection(ProjectPostRepresentation $post_representation): array
    {
        $fields = [];

        if ($post_representation->fields !== null) {
            foreach ($post_representation->fields as $field_representation) {
                $fields[$field_representation->field_id] = $field_representation->value;
            }
        }

        return $fields;
    }
}
