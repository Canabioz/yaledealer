<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Sections
 *
 * @ORM\Table(name="sections")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\SectionsRepository")
 */
class Sections
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="parent_id", type="integer", nullable=true)
     */
    private $parentId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=255, nullable=true)
     */
    private $path;

    /**
     * @var int
     *
     * @ORM\Column(name="hidden", type="integer")
     */
    private $hidden;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string")
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="pictureName", type="string",nullable=true)
     */
    private $pictureName;

    /**
     *
     * @ORM\Column(name="picture", type="blob",nullable=true)
     */
    private $picture;

    /**
     * @var int
     *
     * @ORM\Column(name="id_date_parsing", type="integer")
     */
    private $idDateParsing;

    /**
     * @var string
     *
     * @ORM\Column(name="log", type="string",nullable=true)
     */
    private $log;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set parentId
     *
     * @param integer $parentId
     *
     * @return Sections
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Get parentId
     *
     * @return int
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Sections
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set path
     *
     * @param string $path
     *
     * @return Sections
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set hidden
     *
     * @param integer $hidden
     *
     * @return Sections
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * Get hidden
     *
     * @return int
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set url
     *
     * @param string $url
     *
     * @return Sections
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set picture
     *
     * @param string $picture
     *
     * @return Sections
     */
    public function setPicture($picture)
    {
        $this->picture = $picture;

        return $this;
    }

    /**
     * Get picture
     *
     * @return string
     */
    public function getPicture()
    {
        return $this->picture;
    }

    /**
     * Set idDateParsing
     *
     * @param integer $idDateParsing
     *
     * @return Sections
     */
    public function setIdDateParsing($idDateParsing)
    {
        $this->idDateParsing = $idDateParsing;

        return $this;
    }

    /**
     * Get idDateParsing
     *
     * @return integer
     */
    public function getIdDateParsing()
    {
        return $this->idDateParsing;
    }

    /**
     * Set pictureName
     *
     * @param string $pictureName
     *
     * @return Sections
     */
    public function setPictureName($pictureName)
    {
        $this->pictureName = $pictureName;

        return $this;
    }

    /**
     * Get pictureName
     *
     * @return string
     */
    public function getPictureName()
    {
        return $this->pictureName;
    }

    /**
     * Set log
     *
     * @param string $log
     *
     * @return Sections
     */
    public function setLog($log)
    {
        $this->log = $log;

        return $this;
    }

    /**
     * Get log
     *
     * @return string
     */
    public function getLog()
    {
        return $this->log;
    }
}
