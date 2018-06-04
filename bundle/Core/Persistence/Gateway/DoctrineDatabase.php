<?php

namespace Netgen\Bundle\InformationCollectionBundle\Core\Persistence\Gateway;

use Doctrine\DBAL\Connection;
use PDO;

class DoctrineDatabase
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $connection;

    protected $objectsWithCollectionsQuery = <<<EOD
SELECT DISTINCT ezcontentobject.id AS content_id,
	ezcontentobject.name,
	ezcontentobject_tree.main_node_id,
	ezcontentclass.serialized_name_list,
	ezcontentclass.identifier AS class_identifier
FROM ezcontentobject,
	ezcontentobject_tree,
	ezcontentclass
WHERE ezcontentobject_tree.contentobject_id = ezcontentobject.id
AND ezcontentobject.contentclass_id = ezcontentclass.id
AND ezcontentclass.version = 0
AND ezcontentobject.id IN
( SELECT DISTINCT ezinfocollection.contentobject_id FROM ezinfocollection )
ORDER BY ezcontentobject.name ASC
LIMIT ?, ?
EOD;

    protected $objectsWithCollectionCountQuery = <<<EOD
SELECT COUNT(*) as count
FROM ezcontentobject,
	ezcontentobject_tree,
	ezcontentclass
WHERE ezcontentobject_tree.contentobject_id = ezcontentobject.id
AND ezcontentobject.contentclass_id = ezcontentclass.id
AND ezcontentclass.version = 0
AND ezcontentobject.id IN
( SELECT DISTINCT ezinfocollection.contentobject_id FROM ezinfocollection )
ORDER BY ezcontentobject.name ASC;
EOD;


    protected $contentsWithCollectionsCountQuery = <<<EOD
    SELECT COUNT( DISTINCT ezinfocollection.contentobject_id ) as count
FROM ezinfocollection,
	ezcontentobject,
	ezcontentobject_tree
WHERE ezinfocollection.contentobject_id = ezcontentobject.id
AND ezinfocollection.contentobject_id = ezcontentobject_tree.contentobject_id
EOD;

    /**
     * DoctrineDatabase constructor.
     *
     * @param \Doctrine\DBAL\Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Returns number of content objects that have any collection
     *
     * @return int
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getContentsWithCollectionsCount()
    {
        $query = $this->connection->prepare($this->contentsWithCollectionsCountQuery);

        $query->execute();

        return (int)$query->fetchColumn(0);
    }

    /**
     * Returns content objects with their collections
     *
     * @param int $limit
     * @param int $offset
     *
     * @return array
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getObjectsWithCollections($limit, $offset)
    {
        $query = $this->connection->prepare($this->objectsWithCollectionsQuery);
        $query->bindParam(1, $offset, PDO::PARAM_INT);
        $query->bindParam(2, $limit, PDO::PARAM_INT);

        $query->execute();

        return $query->fetchAll();
    }

    /**
     * Returns collections count
     *
     * @return int
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getObjectsWithCollectionCount()
    {
        $query = $this->connection->prepare($this->objectsWithCollectionCountQuery);

        $query->execute();

        return (int)$query->fetchColumn(0);
    }
}