<?php

declare(strict_types=1);


namespace App\model;

class Article
{
    private ?int $id_article = null;
    private ?string $cover_image = null;
    private ?string $published_at = null;
    private string $created_at;
    private string $title;
    private string $slug;
    private string $content;
    private ?string $introduction = null;
    private ?int $id_user = null;
    private ?array $categories = null;
    private ?array $tags = null;

    public function __construct(array $data = [])
    {
        $this->hydrate($data);
    }

    public function hydrate(array $data): void
    {
        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id_article;
    }

    public function getCoverImage(): ?string
    {
        return $this->cover_image;
    }

    public function getPublishedAt(): ?string
    {
        return $this->published_at;
    }

    public function getCreatedAt(): string
    {
        return $this->created_at;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getUserId(): ?int
    {
        return $this->id_user;
    }

    public function getCategories(): ?array
    {
        return $this->categories;
    }

    // Setters
    public function setId_article(?int $id_article): self
    {
        $this->id_article = $id_article;
        return $this;
    }

    public function setCover_image(?string $cover_image): self
    {
        $this->cover_image = $cover_image;
        return $this;
    }

    public function setPublished_at(?string $published_at): self
    {
        $this->published_at = $published_at;
        return $this;
    }

    public function setCreated_at(string $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function setId_user(?int $id_user): self
    {
        $this->id_user = $id_user;
        return $this;
    }

    public function setCategories(?array $categories): self
    {
        $this->categories = $categories;
        return $this;
    }

    public function getIntroduction(): ?string
    {
        return $this->introduction;
    }

    public function setIntroduction(?string $introduction): self
    {
        $this->introduction = $introduction;
        return $this;
    }

    // Méthode pour convertir l'objet en tableau
    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags($tags): self
    {
        if (is_string($tags)) {
            // Si c'est une chaîne JSON, on la décode
            $decoded = json_decode($tags, true);
            $this->tags = $decoded !== null ? $decoded : null;
        } else {
            // Si c'est déjà un tableau ou null
            $this->tags = $tags;
        }
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id_article' => $this->id_article,
            'cover_image' => $this->cover_image,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'title' => $this->title,
            'slug' => $this->slug,
            'introduction' => $this->introduction,
            'content' => $this->content,
            'id_user' => $this->id_user,
            'categories' => $this->categories,
            'tags' => $this->tags
        ];
    }

    // Méthode pour générer un slug à partir du titre
    public function generateSlug(): void
    {
        if (!empty($this->title)) {
            $baseSlug = strtolower($this->title);
            $baseSlug = preg_replace('/[^a-z0-9]+/', '-', $baseSlug);
            $baseSlug = trim($baseSlug, '-');

            // Ajoute un timestamp court pour rendre le slug unique
            $timestamp = substr((string)time(), -4);
            $this->setSlug($baseSlug . '-' . $timestamp);
        }
    }
}
