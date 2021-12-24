<?php

namespace DeLoachTech\AppAccessControl;

class AppAccessControl
{
    private $config;
    private $versionId;
    private $persistenceClass;


    public function __construct(
        string $accountId,
        string $defaultVersionId,
        object $persistenceClass,
        string $dataFile = __DIR__ . '/app-access-control.json'
    )
    {
        if (file_exists($dataFile)) {

            if ($persistenceClass instanceof PersistenceInterface) {

                $this->persistenceClass = $persistenceClass;
                $this->persistenceClass->setDataFile($dataFile);

                if(!$versionId = $persistenceClass->getAccountVersionId($accountId)){
                    $this->setAccountVersionId($accountId, $defaultVersionId);
                    $versionId = $defaultVersionId;
                }
                $this->versionId = $versionId;

                $this->config = json_decode(file_get_contents($dataFile), true);

            } else {
                throw new \Exception('Persistence class must implement ' . __NAMESPACE__ . '/PersistenceInterface');
            }


        } else {
            throw new \Exception('Unknown app access control config file ' . $dataFile);
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
        return $this->config['data']['versions'][$id] ?? [];
    }

    public function getAllRoles()
    {
        $roles = $this->config['data']['versions'][$this->versionId]['roles'];
        usort($roles, function ($item1, $item2) {
            return $item1['title'] <=> $item2['title'];
        });
        return $roles;
    }

    public function getRolesById(array $roleIds): array
    {
        $a = [];
        foreach ($roleIds as $roleId) {
            $a[$roleId] = $this->config['data']['versions'][$this->versionId]['roles'][$roleId]['title'] ?? null;
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
        foreach ($this->config['data']['versions'] ?? [] as $k => $v) {
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
        if (isset($this->config['data']['versions'][$this->versionId]['access'][$feature])) {

            if ($permission == null) {
                // There's no permission set. Return true if the user has at least one of the roles.
                return count(array_intersect(array_keys($this->config['data']['versions'][$this->versionId]['access'][$feature]), $roles ?? [])) > 0;
            }

            foreach ($roles as $role) {
                if (!empty($this->config['data']['versions'][$this->versionId]['access'][$feature][$role])) {
                    if (
                        in_array($permission, $this->config['data']['versions'][$this->versionId]['access'][$feature][$role]) ||
                        in_array($this->config['data']['permission_all'] ?? [], $this->config['data']['versions'][$this->versionId]['access'][$feature][$role])
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

    public function getConfig()
    {
        return $this->config;
    }

}