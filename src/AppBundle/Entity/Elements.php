<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Elements
 *
 * @ORM\Table(name="elements")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ElementsRepository")
 */
class Elements
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
     * @ORM\Column(name="parent_id", type="integer")
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
     * @ORM\Column(name="part_num", type="string", length=255)
     */
    private $partNum;

    /**
     * @var string
     *
     * @ORM\Column(name="qty", type="string", length=255)
     */
    private $qty;


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
     * @return Elements
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
     * @return Elements
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
     * Set partNum
     *
     * @param string $partNum
     *
     * @return Elements
     */
    public function setPartNum($partNum)
    {
        $this->partNum = $partNum;

        return $this;
    }

    /**
     * Get partNum
     *
     * @return string
     */
    public function getPartNum()
    {
        return $this->partNum;
    }

    /**
     * Set qty
     *
     * @param string $qty
     *
     * @return Elements
     */
    public function setQty($qty)
    {
        $this->qty = $qty;

        return $this;
    }

    /**
     * Get qty
     *
     * @return string
     */
    public function getQty()
    {
        return $this->qty;
    }
}

