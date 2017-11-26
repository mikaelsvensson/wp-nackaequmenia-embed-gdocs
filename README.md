# Wordpress-tillägg för Nacka Equmenias krisplaner

Det här tillägget aktiverar två nya Wordpress Shortcodes:
- nackasmu_gdoc
- nackasmu_actionplan_precautions

_För att säkerställa kompatibilitet med det här tillägget så bör nya handlingsplaner skapas genom att
kopiera Google Docs-dokumentet "Krisplan - Mall för handlingsplan"._

## nackasmu_gdoc

Detta tillägg gör så att du kan inkludera handlingsplaner (krisplaner) från Google Docs i dina Wordpress-inlägg.

För att detta ska fungera ställs följande krav på dokumentet som ska importeras:
 - Dokumentet måste vara publikt, dvs. gjorts tillgängligt för hela världen via Google Docs-funktionen _Publicera på webben_.
 
Argument:
 - url: Publika adressen till dokumentet
 - cache_seconds: Antalet sekunder som dokument ska cachas (sätt till 0 vid felsökning)

Exempel på Google Docs-länkar som hanteras av tillägget:

```[nackasmu_gdoc url="https://docs.google.com/document/u/1/d/e/2PACX-1vRua2blMAdlGCHXq_vOP6x05-O-jDvBEfAkrQr8lyBPsJI_2H30AU9u-de0tpxw0MbQ5redUh8-x_QS/pub"]```

```[nackasmu_gdoc url="https://docs.google.com/document/d/151JMROtxZsAok6E3zSIHXKePxDgxEU7zPC7AjqRsjLw/pub"]```

## nackasmu_actionplan_precautions

Detta tillägg gör så att du kan inkludera en automatiskt genererad lista över förebyggande trygghets- och 
säkerhetsåtgärder i dina Wordpress-inlägg. Listan genereras utifrån de Google Docs-dokument som refereras i
de "nackasmu_gdoc-shortcodes" som används i inlägg som tillhör en viss kategori.

För att detta ska fungera ställs följande krav på dokumentet som ska importeras:

 - För att hitta de handlingsplaner som ska ligga till grund för listan så gör tillägget så här:
   1. Hitta inlägg som tillhör en viss kategori, ex. "Handlingsplaner".
   1. Sök igenom inläggens brödtext och leta efter adresser i ```[nackasmu_gdoc url="..."]```.
 - De dokument (handlingsplaner) som refereras måste uppfylla följande krav:
   1. Åtgärder måste definieras som punktlistor.
   1. En lista med åtgärder måste föregås av ett vanligt stycke text som inleds med texten "Åtgärder för att".

Argument:
 - post_category: Den kategori av inlägg som ska genomsökas i jakt på dokument (handlingsplaner) att analysera
 - cache_seconds: Antalet sekunder som dokument ska cachas (sätt till 0 vid felsökning)

Exempel på användning:

```[nackasmu_actionplan_precautions post_category="Handlingsplan"]```


## Utveckla lokalt

Starta webbserver och databas i Docker
    
    $docker-compose up
    
Gå till http://localhost:8081/wp-admin/


