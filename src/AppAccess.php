<?php
/**
 * This class has to be generic enough to user in multiple frameworks.
 * You can extend this class into a service and instantiate it there.
 * Then use the service to access these methods and add functionality.
 */
namespace DeLoachTech\AppAccess;

use Exception;

class AppAccess
{
    private $data;
    private $versionId;
    private $persistenceClass;


    /**
     * @param mixed $accountId The active account id.
     * @param string $defaultVersionId The default version id if the account has not been assigned one.
     * @param object $persistenceClass Your class used to save account version information. (Must extend the PersistenceClassInterface)
     * @param string $dataFile The file containing the JSON data from the remote service.
     * @throws Exception
     */
    public function __construct(
        $accountId,
        string $defaultVersionId,
        object $persistenceClass,
        string $dataFile = __DIR__ . '/app-access.json'
    )
    {
        if (file_exists($dataFile)) {

            if ($persistenceClass instanceof PersistenceClassInterface) {

                $this->persistenceClass = $persistenceClass;
                $this->persistenceClass->setup($dataFile);

                if (!empty($accountId)) {

                    if (!$versionId = $persistenceClass->getAccountVersionId($accountId)) {
                        $this->setAccountVersionId($accountId, $defaultVersionId);
                        $versionId = $defaultVersionId;
                    }
                    $this->versionId = $versionId;
                }

                $this->data = json_decode(file_get_contents($dataFile), true);

            } else {
                throw new Exception('Persistence class must implement ' . __NAMESPACE__ . '\PersistenceClassInterface');
            }

        } else {
            throw new Exception('Unknown app access control data file ' . $dataFile);
        }
    }

    public function setAccountVersionId($accountId, string $versionId): bool
    {
        return $this->persistenceClass->setAccountVersionId($accountId, $versionId);

    }

    public function getAccountVersionId($accountId): string
    {
        return $this->persistenceClass->getAccountVersionId($accountId);
    }


    public function getVersion(string $id): array
    {
        return $this->data['data']['versions'][$id] ?? [];
    }

    public function getAllRoles()
    {
        $roles = $this->data['data']['versions'][$this->versionId]['roles'];
        usort($roles, function ($item1, $item2) {
            return $item1['title'] <=> $item2['title'];
        });
        return $roles;
    }

    public function getRolesById(array $roleIds): array
    {
        $a = [];
        foreach ($roleIds as $roleId) {
            $a[$roleId] = $this->data['data']['versions'][$this->versionId]['roles'][$roleId]['title'] ?? null;
        }
        asort($a);
        return array_filter($a);
    }

    /**
     * Returns an array of versions matching $tags sorted by $sortKey.
     * If $tags is an array, in_array() is used against version tag.
     * If $tags is a string, fnmatch() is used against version tag permitting wildcards.
     * @param mixed $tags
     * @param string $sortKey Default is 'tag'
     * @return array
     */
    public function getTaggedVersions($tags, string $sortKey = 'tag'): array
    {
        $a = [];
        foreach ($this->data['data']['versions'] ?? [] as $k => $v) {
            if (is_array($tags)) {
                if (in_array($v['tag'], $tags)) {
                    $a[$k] = $v;
                }
            } else {
                if (fnmatch($tags, $v['tag'])) {
                    $a[$k] = $v;
                }
            }
        }
        usort($a, function ($item1, $item2) use ($sortKey) {
            return $item1[$sortKey] <=> $item2[$sortKey];
        });
        return $a;
    }

    public function canRoleAccessFeature(string $feature, array $roles, string $permission = null): bool
    {
        if (isset($this->data['data']['versions'][$this->versionId]['access'][$feature])) {

            if ($permission == null) {
                // There's no permission set. Return true if the user has at least one of the roles.
                return count(array_intersect(array_keys($this->data['data']['versions'][$this->versionId]['access'][$feature]), $roles ?? [])) > 0;
            }

            foreach ($roles as $role) {
                if (!empty($this->data['data']['versions'][$this->versionId]['access'][$feature][$role])) {
                    if (
                        in_array($permission, $this->data['data']['versions'][$this->versionId]['access'][$feature][$role]) ||
                        in_array($this->data['data']['permission_all'] ?? [], $this->data['data']['versions'][$this->versionId]['access'][$feature][$role])
                    ) {
                        return true;
                    }
                }
            }
        } else {
            //file_put_contents($this->errorFile, 'Unknown feature ' . $feature . "\n", FILE_APPEND);
        }
        return false;
    }

    public function getData()
    {
        return $this->data;
    }

}