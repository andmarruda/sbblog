<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute precisa ser aceito.',
    'accepted_if' => ':attribute precisa ser aceito quando :other é :value.',
    'active_url' => ':attribute não é uma URL válida.',
    'after' => ':attribute precisa ser uma data superior a :date.',
    'after_or_equal' => ':attribute precisa ser uma data superior ou igual a :date.',
    'alpha' => ':attribute deve conter somente letras.',
    'alpha_dash' => ':attribute deve conter somente letras, números, traço e underline.',
    'alpha_num' => ':attribute deve conter somente letras e números.',
    'array' => ':attribute deve ser do tipo array.',
    'before' => ':attribute precisa ser uma data anterior a :date.',
    'before_or_equal' => ':attribute precisa ser uma data anterior ou igual a :date.',
    'between' => [
        'numeric' => ':attribute precisa estar entre :min e :max.',
        'file' => ':attribute deve ter entre :min e :max kb.',
        'string' => ':attribute precisa ter entre :min e :max caracteres.',
        'array' => ':attribute precisa ter entre :min e :max items.',
    ],
    'boolean' => ':attribute precisa ser verdadeiro ou falso.',
    'confirmed' => ':attribute não é uma confirmação correspondente.',
    'current_password' => 'A senha está incorreta.',
    'date' => ':attribute não é uma data válida.',
    'date_equals' => ':attribute precisa ser uma data igual a :date.',
    'date_format' => ':attribute não corresponde com o formato :format.',
    'declined' => ':attribute precisa ser rejeitado.',
    'declined_if' => ':attribute precisa ser recusado quando :other é :value.',
    'different' => ':attribute e :other precisam ser diferentes.',
    'digits' => ':attribute precisa ter :digits dígitos.',
    'digits_between' => ':attribute precisa ter entre :min e :max dígitos.',
    'dimensions' => ':attribute a imagem tem dimensões inválidas.',
    'distinct' => ':attribute o campo tem um valor duplicado.',
    'email' => ':attribute precisa ser um endereço de email válido.',
    'ends_with' => ':attribute precisa terminar em um dos seguintes: :values.',
    'enum' => ':attribute não corresponde a nenhum valor esperado.',
    'exists' => ':attribute não existe.',
    'file' => ':attribute precisa ser um arquivo.',
    'filled' => ':attribute campo precisa ter um valor.',
    'gt' => [
        'numeric' => ':attribute precisa ser maior que :value.',
        'file' => ':attribute precisa ser maior que :value kilobytes.',
        'string' => ':attribute precisa conter mais de :value caracteres.',
        'array' => ':attribute precisa conter mais de :value items.',
    ],
    'gte' => [
        'numeric' => ':attribute precisa ser maior ou igual a :value.',
        'file' => ':attribute precisa ser maior ou igual a :value kilobytes.',
        'string' => ':attribute precisa conter mais ou igual a :value caracteres.',
        'array' => ':attribute precisa conter :value items ou mais.',
    ],
    'image' => ':attribute precisa ser uma imagem.',
    'in' => ':attribute não é válido.',
    'in_array' => ':attribute o valor não existe em :other.',
    'integer' => ':attribute deve ser um número inteiro.',
    'ip' => ':attribute deve ser um endereço de IP válido.',
    'ipv4' => ':attribute deve ser um endereço de IPv4 válido.',
    'ipv6' => ':attribute deve ser um endereço de IPv6 válido.',
    'json' => ':attribute deve ser uma string do tipo JSON.',
    'lt' => [
        'numeric' => ':attribute deve ser menor de :value.',
        'file' => ':attribute deve ser menor do que :value kilobytes.',
        'string' => ':attribute deve conter menos que :value caracteres.',
        'array' => ':attribute deve conter menos que :value items.',
    ],
    'lte' => [
        'numeric' => ':attribute deve ser menor ou igual a :value.',
        'file' => ':attribute deve ser menor ou igual a :value kilobytes.',
        'string' => ':attribute deve conter menos ou igual a :value caracteres.',
        'array' => ':attribute não pode ter mais que :value items.',
    ],
    'mac_address' => ':attribute deve ser um endereço MAC válido.',
    'max' => [
        'numeric' => ':attribute não deve ser maior que :max.',
        'file' => ':attribute não deve ser maior que :max kilobytes.',
        'string' => ':attribute não deve ser maior que :max caracteres.',
        'array' => ':attribute não deve ser maior que :max items.',
    ],
    'mimes' => ':attribute precisa ser um arquivo do tipo: :values.',
    'mimetypes' => ':attribute o arquivo precisa ser do tipo: :values.',
    'min' => [
        'numeric' => ':attribute precisa ter no mínimo :min.',
        'file' => ':attribute precisa ter no mínimo :min kilobytes.',
        'string' => ':attribute deve conter no mínimo :min caracteres.',
        'array' => ':attribute deve conter pelo menos :min items.',
    ],
    'multiple_of' => ':attribute deve ser um múltiplo de :value.',
    'not_in' => ':attribute não é um valor válido.',
    'not_regex' => ':attribute formato é inválido.',
    'numeric' => ':attribute deve ser um número.',
    'password' => 'A senha está incorreta.',
    'present' => ':attribute campo deve estar presente.',
    'prohibited' => ':attribute campo é proíbido.',
    'prohibited_if' => ':attribute o campo é proíbido quando :other é :value.',
    'prohibited_unless' => ':attribute campo é proíbido exceto quando :other está em :values.',
    'prohibits' => ':attribute campo proíbido :other por este estar presente.',
    'regex' => ':attribute formato é inválido.',
    'required' => ':attribute campo é obrigatório.',
    'required_array_keys' => ':attribute campo deve conter entradas para: :values.',
    'required_if' => ':attribute campo é obrigatório quando :other é :value.',
    'required_unless' => ':attribute campo é obrigatório exceto se :other está em :values.',
    'required_with' => ':attribute campo é obrigatório quando :values estão presentes.',
    'required_with_all' => ':attribute campo é obrigatório quando :values não estão presentes.',
    'required_without' => ':attribute campo é obrigatório quando :values não está presente.',
    'required_without_all' => ':attribute campo é obrigatório quando nenhum dos valores :values estão presentes.',
    'same' => ':attribute e :other precisam ser iguais.',
    'size' => [
        'numeric' => ':attribute deve ser :size.',
        'file' => ':attribute deve ser :size kilobytes.',
        'string' => ':attribute deve conter :size caracteres.',
        'array' => ':attribute deve conter :size items.',
    ],
    'starts_with' => ':attribute deve começar com um dos seguintes: :values.',
    'string' => ':attribute deve ser um texto.',
    'timezone' => ':attribute deve conter um timezone válido.',
    'unique' => ':attribute já está em uso.',
    'uploaded' => ':attribute upload falhou.',
    'url' => ':attribute deve ser uma URL válida.',
    'uuid' => ':attribute deve ser um UUID válido.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
