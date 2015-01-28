<?php
/**
 * @package   AllediaBuilder
 * @contact   www.alledia.com, hello@alledia.com
 * @copyright 2015 Alledia.com, All rights reserved
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

require_once 'phing/Task.php';
require_once __DIR__ . '/GitCommandTask.php';

class GitTaggedTask extends GitCommandTask
{
    /**
     * Check if we already have created a tag for the HEAD commit on the current branch
     *
     * @return void
     */
    public function main()
    {
        $this->log('Checking if HEAD is tagged, for ' . $this->path, Project::MSG_VERBOSE);

        // Get the hash of the HEAD commit
        $hashHead = $this->git('rev-list HEAD -1');
        $hashHead = preg_replace('/[\r\n]*/i', '', $hashHead);

        // Get the hashes of all tags
        $hashTags = $this->git('show-ref --tags');
        $hashTags = explode("\n", $hashTags);

        // Check if the head commit is the head of any tag
        $tagged = false;
        foreach ($hashTags as $hashTag) {
            list($hash, $tag) = explode(' ', $hashTag);
            $tag = str_replace('refs/tags/', '', $tag);

            if ($hash === $hashHead) {
                $tagged = true;
                break;
            }
        }

        if (!$tagged) {
            $this->log("{$this->projectName}'s HEAD commit: {$hashHead}. Create a tag for it or use the correct branch", Project::MSG_WARN);
            throw new BuildException($this->projectName . " was not released. Create a tag for the current HEAD commit, or use the correct branch");
        }

        // Extract version from the tag name
        $version = preg_replace('/^v/', '', $tag);

        // Extract the release type from tag name
        preg_match('/[0-9]\-([a-z]*)/i', $tag, $matches);
        if (isset($matches[1])) {
            $releaseType = $matches[1];
        } else {
            $releaseType = 'stable';
        }

        $this->project->setProperty('current-tag', $tag);
        $this->project->setProperty('current-tag-version', $version);
        $this->project->setProperty('current-tag-release-type', $releaseType);
    }
}
