<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $etat;

    #[ORM\Column(type: 'string', length: 255)]
    private $token;

    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: CommandeProduit::class, orphanRemoval: true, cascade: ['persist'])]
    private $commandeProduits;

    public function __construct()
    {
        $this->commandeProduits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): self
    {
        $this->etat = $etat;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return Collection<int, CommandeProduit>
     */
    public function getCommandeProduits(): Collection
    {
        return $this->commandeProduits;
    }

    public function addCommandeProduit(CommandeProduit $commandeProduit): self
    {
        if (!$this->commandeProduits->contains($commandeProduit)) {
            $this->commandeProduits[] = $commandeProduit;
            $commandeProduit->setCommande($this);
        }

        return $this;
    }

    public function removeCommandeProduit(CommandeProduit $commandeProduit): self
    {
        if ($this->commandeProduits->removeElement($commandeProduit)) {
            // set the owning side to null (unless already changed)
            if ($commandeProduit->getCommande() === $this) {
                $commandeProduit->setCommande(null);
            }
        }

        return $this;
    }
}



$arr  = ['89_100_70-2023', '89_100_70-58912', '89_100_70-12694' , '6955_6314-952', '5_4-598623' , '536978-953624', '89_89_89_89_89_89_89_89_89_89_89_89_100-2023'];

// echo preg_replace('/(.+)(\-)(\d+)$/i', '$1', '89_89_89_89_89_89_89_89_89_89_89_89_100-2023');

foreach($arr as $element){
    // echo $element ."<br>\n";
    echo preg_replace('/^(.+)(\-)(\d+)$/i', '$1', $element) ."\n";
}




// $string = 'April 15, 2003';
// $pattern = '/(\w+) (\d+), (\d+)/i';
// $replacement = '${1}1,$3';
// echo preg_replace($pattern, $replacement, $string);