<?php
/**
 * This class has to be generic enough to user in multiple frameworks.
 * You can extend this class into a service and instantiate it there.
 * Then use the service to access these methods and add functionality.
 * You can also use the service as the data persistence class!
 */

namespace DeLoachTech\Zepher;

use Exception;

class Zepher
{
    private $config;
    private $versionId;
    private $persistenceClass;
    private $domainVersions;


    /**
     * @param mixed $accountId The active account id (if any).
     * @param mixed $domainId The current account domain id.
     * @param object $persistenceClass Your class used to save account version information. (Must extend the PersistenceClassInterface)
     * @param string $configFile The file containing the JSON data from the remote service. (Usually zepher.json)
     * @throws Exception
     */
    public function __construct(
        $accountId,
        ?string $domainId,
        object $persistenceClass,
        string $configFile = __DIR__ . '/zepher.json'
    )
    {
        if (file_exists($configFile)) {

            if ($persistenceClass instanceof PersistenceClassInterface) {

                $this->persistenceClass = $persistenceClass;
                $this->persistenceClass->setup($configFile, $accountId, $domainId);

                $this->config = json_decode(file_get_contents($configFile), true);

                if (isset($domainId)) {
                    $this->domainVersions = $this->config['data']['domains'][$domainId]['versions']; // [tag => id]

                    if (count($this->domainVersions) == 0) {
                        throw new Exception('There are no versions assigned to domain "' . $domainId . '"');
                    } else {
                        ksort($this->domainVersions);
                    }
                }

                if (isset($accountId)) {
                    if(!$this->versionId =  $this->getAccountVersionId($accountId)){
                        $this->versionId = reset($this->domainVersions); // The first version is the default
                        $this->setAccountVersionId($accountId, $this->versionId);
                    }
                }

            } else {
                throw new Exception('Persistence class must implement ' . __NAMESPACE__ . '\PersistenceClassInterface');
            }
        } else {
            throw new Exception('Unknown zepher config file ' . $configFile);
        }
    }

    public function getCurrentVersionId(): ?string
    {
        return $this->versionId;
    }

    public function setAccountVersionId($accountId, string $versionId): bool
    {
        if (!$this->persistenceClass->setVersionId($accountId, $versionId)) {
            throw new Exception('Failed to set account access version id.');
        }
        return true;
    }

    public function getAccountVersionId($accountId): ?string
    {
        return $this->persistenceClass->getVersionId($accountId);
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


    public function getAppDomains(){
        return $this->config['data']['domains'];
    }

    public function getCurrentVersions(): array
    {
        $versions = [];
        foreach ($this->domainVersions as $tag => $id) {
            $versions[] = $this->config['data']['versions'][$this->versionId];
        }
        return $versions;
    }

}