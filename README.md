# Smartmeter2 - Webinterface elektra- en gasverbruik
*Work-in-progress*: Mijn oude `Smartmeter` webinterface laat alleen de standen en het verbruik per dag en per maand zien maar niet de kosten.
Door een gedeeltelijke overgang van gas naar elektra wil ik ook graag de *kosten* kunnen zien, wat doet die overgang "onder de streep"?

Met deze nieuwe webinterface wil ik het verbruik en de kosten, per dag, week, en maand kunnen zien, met ter vergelijking de getallen van voorgaande jaren.
Om die vergelijking te kunnen maken gebruik ik het *huidige* verbruikstarief ook voor de voorgaande jaren. Anders valt het niet te vergeljken.

## Tabs
De webinterface heeft bovenaan een aantal tabs:
- Links tabs voor het selecteren van het jaar.
- Rechts tabs voor het selecteren van het dag, week, of maand overzicht.

## Kolommen
De webinterface laat de kosten per dag, week, of maand zien, voor het geselecteerde jaar, met de laatste periode bovenaan.
- **Periode** geeft de dag, week of maand weer bijvoorbeeld `14 oktober 2024`, `week 42 2024`, of `oktober 2024`.
- **Beginstanden** en **Eindstanden** zijn elk 4 kolommen voor `elektra-T1`, `elektra-T2`, `elektra` en `gas`.
- **Verbruik** zijn twee kolommen voor het `elektra` en `gas` verbruik in de betreffende periode.
- **Dit jaar** zijn twee kolommen voor het `elektra` en `gas` verbruik tot en met de betreffende periode.
- **Kosten** zijn drie kolommen voor de `elektra`, `gas`, en het totaal in de betreffende periode.
- **Historie** zijn kolommen met de totale kosten voor de overeenkomstige periode in voorgaande jaren.

De data in `Historie` kolommen geven de volgende periodes weer:
- Per dag de totale kosten over dezelfde datum in de voorgaande jaren, ook voor de huidige (gedeeltelijke) dag.
- Per week de totale kosten over dezelfde data (dus niet de week) in de voorgaande jaren. Voor de huidige week, tot en met de betreffende dag de week.
- Per maand de totale kosten over dezelfde maand in de voorgaande jaren. Voor de huidige week, tot en met de betreffende dag van de maand.

Dat zijn een hoop kolommen die hopelijk zonder horizontale scroll te zien zijn. 
Wellicht splits ik het op in aparte `standen` en een `verbruik` schermen.

