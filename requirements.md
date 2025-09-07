# OpenAPi Documentation for Spatie Data package

## Wprowadzenie

Celem paczki jest parsowanie Klas DTO ze SPatie Data i na ich podstawie wygenerowanie jsona do OpenAPi >=3.1.

Paczka powinna byc paczką PHP z dla frameworka laravel.

Najlepiej używając type hintów zmiennych lub ewentualnie php reflection.

Paczka powinna przede wszystkim wyświetlić 
`php artisan route:list`

na tej podstawie będziemy tworzyć api docs.
sprawdzamy controllery, metody, parametry, docs również i to wszystko będzie parsowane.

Po wygenerowaniu routów mamy kontrollery które obsługują dany route, tam będą informacje również o parametrach, jeżeli będzie:
- zwykly parametr z type hintem - to wiadomo
- klasa spatie dto to wtedy uruchamiamy nasz parser któ©y wszystko wyciągnie rekturencyjnie
- klasa typu laravel FormRequest trzeba z pól do walidacji odczytać do powinno być czym.

##### responses
responses, kody itd wszystko odczytujemy również z kontrollerów. Jeżeli nie ma stricte 
```php
return response()->json();
```
to domyślnie status jest 200 i na podstawie resource który jest zwracany mozemy również odczytać pola z response.
Mogą być 2 przypadki
- new UserResource($user),
- UserResource::collection($users)

ale to wtedy również odczytujemy pola z np UserResource z tym że to będzie array jako response przy kolekcji.

Po wygenerowaniu takiego jsona dobrze by było miec jakis parser który wyświetliłby dokumentacje w swagger lub recdoc


### namespace 
pjadanowski/openapi-generator



