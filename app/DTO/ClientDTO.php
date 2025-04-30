<?php 

declare(strict_types=1);

namespace App\DTO;

use App\Interfaces\JsonSerializableInterface;
use Ramsey\Uuid\Uuid;

class ClientDTO implements JsonSerializableInterface
{
    /**
     * Client's full name
     * @var string
     */
    private string $fullName;

    /**
     * Client's email
     * @var string
     */
    private string $email;

    /**
     * Client's phone number
     * @var string
     */
    private string $phone;

    /**
     * Note about the client
     * @var string
     */
    private string $note;

    /**
     * Date when the client was created
     * @var int
     */
    private int $dataCreated;

    public function __construct()
    {
        $this->fullName = '';   
        $this->email = '';
        $this->phone = '';
        $this->note = '';
        $this->dataCreated = 0;
    }

    /**
     * Set the client's full name
     *
     * @param string $fullName
     * @return void
     */
    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    /**
     * Set the client's email
     *
     * @param string $email
     * @return void
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * Set the client's phone number
     *
     * @param string $phone
     * @return void
     */
    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * Set a note about the client
     *
     * @param string $note
     * @return void
     */
    public function setNote(string $note): void
    {
        $this->note = $note;
    }

    /**
     * Set the date when the client was created
     *
     * @param int $dataCreated
     * @return void
     */
    public function setDataCreated(int $dataCreated): void
    {
        $this->dataCreated = $dataCreated;
    }

    /**
     * Get the client's full name
     *
     * @return string
     */
    public function getFullName(): string
    {
        return $this->fullName;
    }

    /**
     * Get the client's email
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Get the client's phone number
     *
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * Get the note about the client
     *
     * @return string
     */
    public function getNote(): string
    {
        return $this->note;
    }

    /**
     * Get the date when the client was created
     *
     * @return int
     */
    public function getDataCreated(): int
    {
        return $this->dataCreated;
    }

    /**
     * Transform the client data into an array
     *
     * @return array{fullName: string, email: string, phone: string, note: string, dataCreated: int}
     */
    public function toArray(): array
    {
        return [
            'fullName' => $this->fullName,
            'email' => $this->email,
            'phone' => $this->phone,
            'note' => $this->note,
            'dataCreated' => $this->dataCreated,
        ];
    }

    /**
     * Serialize the salon data to JSON
     *
     * @return array<string, array{fullName: string, email: string, phone: string, note: string, dataCreated: int}>
     */
    public function jsonSerialize(): array
    {
        $uuid = Uuid::uuid4()->toString();
        $data = [];
        $data[$uuid] = $this->toArray(); // Form an array with the UUID key
        return $data;
    }
}