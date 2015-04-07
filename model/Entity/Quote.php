<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Quote
 *
 * @ORM\Table(name="quotes")
 * @ORM\Entity
 */
class Quote
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

  	/**
  	 * @ORM\ManyToOne(targetEntity="Author", inversedBy="quotes")
  	 * @ORM\JoinColumn(name="id_acount", referencedColumnName="id", onDelete="CASCADE")
  	 */
    private $author;

	  /**
	   * @ORM\Column(name="text", type="string", length=255)
	   */
    private $text;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set text
     *
     * @param string $text
     *
     * @return Quote
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set author
     *
     * @param \Entity\Author $author
     *
     * @return Quote
     */
    public function setAuthor(\Entity\Author $author = null)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return \Entity\Author
     */
    public function getAuthor()
    {
        return $this->author;
    }
}
