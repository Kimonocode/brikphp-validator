<?php

namespace Brikphp\Validator;

use Psr\Http\Message\ServerRequestInterface;

abstract class Validator {

    /**
     * Tableau d'erreurs
     * @var array
     */
    private array $errors = [];

    /**
     * Valide les données d'une requête avec le schema d'une class enfant
     *
     * @param array $data
     * @param ServerRequestInterface|null $request
     * @return bool True si les données ont été validées, False sinon.
     */
    public function validate(array $data, ?ServerRequestInterface $request = null): bool
    {
        $schema = $this->getSchema();
        $this->errors = []; // Réinitialise les erreurs

        foreach ($schema as $field => $rules) {
            if (!isset($data[$field])) {
                if (!empty($rules['required'])) {
                    $this->addError($field, "Le champ est requis.");
                }
                continue;
            }

            $value = $data[$field];

            // Validation du type
            if (isset($rules['type']) && !$this->validateType($value, $rules['type'])) {
                $this->addError($field, "Le type attendu est {$rules['type']}.");
            }

            // Validation de la longueur minimale
            if (isset($rules['min']) && is_string($value) && strlen($value) < $rules['min']) {
                $this->addError($field, "La longueur minimale est de {$rules['min']} caractères.");
            }

            // Validation de la longueur maximale
            if (isset($rules['max']) && is_string($value) && strlen($value) > $rules['max']) {
                $this->addError($field, "La longueur maximale est de {$rules['max']} caractères.");
            }

            // Validation avec une regex
            if (isset($rules['regex']) && is_string($value) && !preg_match($rules['regex'], $value)) {
                $this->addError($field, "Le format est invalide.");
            }

            // Validation de confirmation de champ
            if (isset($rules['confirm'])) {
                if ($request === null) {
                    throw new \InvalidArgumentException("Une instance de ServerRequestInterface est requise pour la règle 'confirm'.");
                }
                $this->validateConfirmation($field, $rules['confirm'], $request);
            }
        }

        return empty($this->errors);
    }
    
    /**
     * Retourne les erreurs de validation.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Retourne le schéma de validation d'une classe enfant.
     * Doit être implémentée dans les sous-classes.
     *
     * @return array
     */
    abstract protected function getSchema(): array;

    /**
     * Valide la confirmation de deux champs.
     *
     * @param string $field
     * @param string $value
     * @param ServerRequestInterface $request
     */
    private function validateConfirmation(string $field, string $value, ServerRequestInterface $request): void
    {
        $parsedBody = $request->getParsedBody();
        $expected = $parsedBody[$value] ?? null;
        $actual = $parsedBody[$field] ?? null;

        if ($expected !== $actual) {
            $this->addError($field, "Les champs '{$field}' et '{$value}' ne correspondent pas.");
        }
    }

    /**
     * Vérifie le type d'une valeur.
     *
     * @param mixed $value
     * @param string $type
     * @return bool
     */
    private function validateType($value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'integer' => is_int($value),
            'float' => is_float($value),
            'array' => is_array($value),
            default => is_a($value, $type),
        };
    }

    /**
     * Ajoute une erreur de validation.
     *
     * @param string $field Champ en erreur
     * @param string $message Message d'erreur
     */
    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}