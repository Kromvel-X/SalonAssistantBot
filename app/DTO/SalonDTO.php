<?php

declare(strict_types=1);

namespace App\DTO;

use App\Interfaces\JsonSerializableInterface;
use Ramsey\Uuid\Uuid;

/**
 * Class SalonDTO
 * Data Transfer Object for Salon
 */
class SalonDTO implements JsonSerializableInterface
{
    /**
     * Salon name
     * @var string
     */
    private string $name;

    /**
     * Salon location
     * @var string
     */
    private string $location;

    /**
     * Salon photos
     * @var array<string, string>
     */
    private array $photos;

    /**
     * Salon email
     * @var string
     */
    private string $email;

    /**
     * Salon phone
     * @var string
     */
    private string $phone;

    /**
     * Salon contact person
     * @var string
     */
    private string $person;

    /**
     * Salon promocode
     * @var string
     */
    private string $promocode;

    /**
     * Salon social links
     * @var array<int, string>
     */
    private array $socLinks;

    /**
     * Salon note
     * @var string
     */
    private string $note;

    public function __construct()
    {
        $this->name = '';
        $this->location = '';
        $this->photos = [];
        $this->email = '';
        $this->phone = '';
        $this->person = '';
        $this->promocode = '';
        $this->socLinks = [];
        $this->note = '';
    }

    /**
     * Set the name of the salon
     *
     * @param string $name name of the salon
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Set the location of the salon
     *
     * @param string $location location of the salon
     * @return void
     */
    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    /**
     * Set the photos of the salon
     *
     * @param string $file_id telegram file ID of the photo
     * @param string $url URL of the photo
     * @return void
     */
    public function setPhotos(string $file_id, string $url): void
    {
        $this->photos[$file_id] = $url;
    }

    /**
     * Set the email of the salon
     *
     * @param string $email email of the salon
     * @return void
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * Set the phone of the salon
     *
     * @param string $phone phone of the salon
     * @return void
     */
    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * Set the contact person of the salon
     *
     * @param string $person contact person of the salon
     * @return void
     */
    public function setPerson(string $person): void
    {
        $this->person = $person;
    }

    /**
     * Set the promocode of the salon
     *
     * @param string $promocode promocode of the salon
     * @return void
     */
    public function setPromocode(string $promocode): void
    {
        $this->promocode = $promocode;
    }

    /**
     * Set the social links of the salon
     *
     * @param string $socLink social link of the salon
     * @return void
     */
    public function setSocLinks(string $socLink): void
    {
        $this->socLinks[] = $socLink;
    }

    /**
     * Set the note of the salon
     *
     * @param string $note note of the salon
     * @return void
     */
    public function setNote(string $note): void
    {
        $this->note = $note;
    }

    /**
     * Get the name of the salon
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the location of the salon
     *
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * Get the photos of the salon
     *
     * @return array<string, string>
     */
    public function getPhotos(): array
    {
        return $this->photos;
    }

    /**
     * Get the email of the salon
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Get the phone of the salon
     *
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * Get the contact person of the salon
     *
     * @return string
     */
    public function getPerson(): string
    {
        return $this->person;
    }

    /**
     * Get the promocode of the salon
     *
     * @return string
     */
    public function getPromocode(): string
    {
        return $this->promocode;
    }

    /**
     * Get the social links of the salon
     *
     * @return array<int, string>
     */
    public function getSocLinks(): array
    {
        return $this->socLinks;
    }

    /**
     * Get the note of the salon
     *
     * @return string
     */
    public function getNote(): string
    {
        return $this->note;
    }
 
    /**
     * Transform the salon data into an array
     *
     * @return array{name: string, location: string, photos: array<string, string>, email: string, phone: string, person: string, promocode: string, socLinks: array<int, string>, note: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'location' => $this->location,
            'photos' => $this->photos,
            'email' => $this->email,
            'phone' => $this->phone,
            'person' => $this->person,
            'promocode' => $this->promocode,
            'socLinks' => $this->socLinks,
            'note' => $this->note,
        ];
    }

    /**
     * Serialize the salon data to JSON
     *
     * @return array<string, array{name: string, location: string, photos: array<string, string>, email: string, phone: string, person: string, promocode: string, socLinks: array<int, string>, note: string}>
     */
    public function jsonSerialize(): array
    {
        $uuid = Uuid::uuid4()->toString();
        $data = [];
        $data[$uuid] = $this->toArray(); // Form an array with the UUID key
        return $data;
    }
}