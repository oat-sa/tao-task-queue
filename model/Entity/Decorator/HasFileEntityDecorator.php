<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoTaskQueue\model\Entity\Decorator;

use oat\generis\model\fileReference\FileSerializerException;
use oat\generis\model\fileReference\UrlFileSerializer;
use oat\oatbox\filesystem\Directory;
use oat\oatbox\filesystem\FileSystemService;
use oat\taoTaskQueue\model\Entity\TaskLogEntityInterface;
use oat\taoTaskQueue\model\QueueDispatcherInterface;
use oat\taoTaskQueue\model\TaskLog\GeneratedFileLocator;

/**
 * HasFileEntityDecorator
 *
 * @author Gyula Szucs <gyula@taotesting.com>
 */
class HasFileEntityDecorator extends TaskLogEntityDecorator
{
    /**
     * @var FileSystemService
     */
    private $fileSystemService;

    /**
     * @var UrlFileSerializer
     */
    private $serializer;

    public function __construct(TaskLogEntityInterface $entity, FileSystemService $fileSystemService, UrlFileSerializer $serializer)
    {
        parent::__construct($entity);

        $this->fileSystemService = $fileSystemService;
        $this->serializer = $serializer;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Add 'hasFile' to the result. Required by our frontend.
     *
     * @return array
     * @throws FileSerializerException
     */
    public function toArray()
    {
        $result = parent::toArray();

        $result['hasFile'] = false;

        $locator = new GeneratedFileLocator($this, $this->fileSystemService, $this->serializer);

        if (($file = $locator->getFile()) && $file->exists()) {
            $result['hasFile'] = true;
        }

        return $result;
    }
}