# Crypto Portfolio Helper - projekt do zarządzania portfolio kryptowalutowym

## Baza danych
Wszystkie tabele posiadają numeryczne ID.

### Tabela currencies (encja/klasa: currency)

Tabela zawierająca dane o walutach, większość ma zastosowanie tylko do kryptowalut, ale tworzenie osobnych encji dla kryptowalut i walut fiducjarnych wprowadzałoby tylko niepotrzebne komplikacje. Można tu znaleźć takie dane jak:
- Nazwa (kolumna "name")
- Symbol (kolumna "symbol") - czyli np PLN dla złotówki czy BTC dla bitcoina
- coinGeckoID (kolumna "coin_gecko_id") - id po którym waluty są odczytywane z API coingecko [tylko dla kryptowalut]
- natywny blockchain (kolumna "native_blockchain") - czyli "ojczysty" blockchain na którym funkcjonuje dana kryptowaluta, ta na którym została oryginalnie stworzona (formalnie rzecz biorąc kryptowalutą nazywa się tylko te, które posiadają własny blockchain, te które funkcjonują na "cudzych" blockchainach określa się mianem tokenów - w projekcie jednak nie ma sensu tego rozróżniać)
- dostępne sieci(blockchainy) (kolumna "available_networks") - lista wszystkich blockchainów na których można przesyłać daną walutę 
- Najwyższa cena Historyczn(ATH) (kolumna "all_time_high")
- Najniższa cena historyczna(ATL) (kolumna "all_time_low")
- Bieżąca cena (kolumna "current_price")
- Maksymalna podaż (kolumna "max_supply") - dotyczy wyłącznie kryptowalut i to też niekoniecznie wszystkich, waluty fiducjarne z natury mogą mieć dowolnie dużą podaż
- Bieżąca podaż (kolumna "current_supply")
- Nisze (kolumna "niches") - lista niszy do jakich należy np Layer1, Stablecoin, Fiat 
- Ostatnia aktualizacja ceny (kolumna "last_price_update")
- waluta cen (kolumna "prices_currency_id") - w jakiej walucie wyrażone są ceny (w tym ATH i ATL)


### Tabela users (encja/klasa: User)

Tabela zawierająca podstawowe dane użytkowników, takie jak: 
 - Imię (kolumna "name") 
 - Nazwisko (kolumna "surname")
 - Nazwa użytkownika (kolumna "user_name")
 - Kraj (kolumna "country")
 - e-mail (kolumna "e_mail")
 - ojczysta i wyświetlana waluta - w obu wypadkach wskazują na konkretny obiekt encji currency; waluta wyświetlana to ta w której domyślnie będą wyświetlane dane

Relacje: 
 - Każdy użytkownik ma dokładnie jedną walutę ojczysta i wyświetlaną
 - Każdy użytkownik może mieć wiele portfoliów
TO DO: dodać hasła i ich haszowanie

### Tabela Exchenges (encja/klasa: Exchange)

Tabela zawierająca dane na temat giełd kryptowalutowych i innych platform do ich zakupu/wymiany. Zawiera dane takie jak:
- Nazwa giełdy (kolumna "name")
- Główny adres (kolumna "main_url") - jeżeli posiada, niektore plaformy mogą istnieć np tylko jako aplikacja.
- Adres API (kolumna "api_address") - do automatycznego pobierania danych z giełdy, obecnie niewykorzystywane
- Klasa parsera (kolumna "parser_class") - nazwa klasy zawierająca parser pozwalający na import danych w plików csv generowanych przez giełdę

Relacje: 
 - Jedna giełda(platforma) może mieć dowolnie wiele tranzakcji (Transaction) na niej przeprowadzonych

### Tabela portflios (encja/klasa: Portfolio)

Jest to podstawowa forma podziału informacji o inwestycjach użytkownika, dzięki niej może podzielić swoje zasoby np na portfolio długo i krótko terminowe, zachowawcze i ryzykowen itd.(podział po giełdach byłby de facto redundancją, choć też jest możliwy). Tabela ta jest przede wszystkim swego rodzaju agregatorem, sama zawiera jedynie podstawowe informacje jak: 
- Nazwa portfolio (kolumna "name")
- id użytkownika (kolumna "user_id") - do którego należy portfolio 
- data rozpoczęcia portfolio (kolumna "starting_date") - domyślnie ustawiona na chwilę stworzenia jej wpisu w bazie
- rozmiar paczki zakupowej (batch_size) - domyślna ilość tranzakcji w jednej paczce zakupowej (batchu) nie jest to twarde ograniczenie i może być przekroczane, służy głównie do automatycznego wykrywania, że dana paczka zakupowa została zakończona.
- domyślny typ batcha (kolumna "default_batch_type")
- czy jest domyślnym portfolio (kolumna "is_default") - użytkownik może mieć portfolio domyślne, do którego będą przypisywane nowo dodane tranzakcje jeśli konkretne portfolio nie zostanie podane. [Do pełnego zaimplementowania]

Relacje: 
 - Każde portfolio należy do dokładnie jednego użytkownika (User)
 - Jedno portfolio może mieć dowolnie wiele (zwykle dużą ilość) paczek zakupowych (TransactionBach)
 

### Tabela transaction_batches (encja/klasa: TransactionBatch)

Paczki tranzakcji (transaction batches) to drugi uzupełniający system podziału w projekcie, Ponieważ użytkownik może kompletnie pominąć ten etap podziału domyślnie rozmiar ten jest równy 1.
- nazwa batcha (kolumna "name")
- data (kolumna "date") - data danej paczki zakupowej, zwykle przyjmuje się za nią datę pierwszej tranzakcji w paczce
- numer porządkowy (kolumna "orginal_number") - czyli która z kolei w portfolio jest dana paczka tranzakcji
- typ (kolumna "type") - czyli jakiego rodzaju jest to paczka - daje użytkownikowi możliwość podziału wewnątrz portfolio na różne rodzaje paczek np moje portfolio DCA ma paczki cotygodniowe ("weekly") i uzupełniające ("catch-up")
- ukończone (kolumna "finished") - czy dana paczka została zakończona, co oznacza, że nie będą do niej dodawane nowe tranzakcje
- id portfolio (kolumna "portfolio id") - którego częścią jest ta paczka

Relacje: 
 - Jedna paczka tranzakcji(batch) może mieć dowolną (choć zwykle małą) liczbę tranzakcji(Transaction)
 - Każda paczka tranzakcji(batch) należy do (jest cześcią) dokładnie jednego portfolio(Portfolio)

### Tabela transactions (encja/klasa: Transaction)

Tabela zawierająca dane dotyczące konkretnych tranzakcji - największa zarówno pod względem zawartych informacji jak i ilości rekordów. Tabela ta zawiera tylko zrealizowane tranzakcje, projekt nie przechowuje informacji o zleceniach(tranzakcjach niezrealizowanych)
- id giełdy (kolumna "exchange_id") - lub platformy na której dokonano tranzakcji
- id paczki tranzakcji (kolumna "transaction_batch_id") - której częścią jest dana tranzakcja
- id zakupionej waluty (kolumna "bought_currency_id")
- ilość kupionej waluty (kolumna "buy_value")
- id sprzedanej waluty (kolumna "sold_currency_id")
- ilość sprzedanej waluty (kolumna "sell_value")
- id waluty prowizji (kolumna "fee_currency_id") - najczęściej takie same jak waluty sprzedawanej lub kupowanej
- wielkość prowizji (kolumna "fee")
- cena rynkowa (kolumna "market_price")
- efektywna cena (kolumna "effective_price") - uwzględniająca prowizję, czyli po jakiej cenie realnie została nabyta dana waluta. Zależnie od tego czy jest płacona w walucie sprzedawanej czy kupowanej może zwiększać ilość zapłaconą lub zakupioną, więc jeżeli prowizja była niezerowa zawsze efektywna cena będzie niższa od rynkowej
- data (kolumna "date") - data realizacji tranzakcji

Relacje: 
 - Każda tranzakcja należy do (jest częścią) dokładnie jednej paczki tranzakcji(TransactionBatch)
 - Każda tranzakcja ma dokładnie po jednej walucie(Currency) zakupionej, sprzedawanej i prowizji
 - Każda tranzakcja ma dokładnie (została zrealizowana na) jedną giełdę(Exchange)

### Tabela daily_exchange_rates (encja/klasa: DailyExchangeRate) 

Tabela zawierająca dzienne kursy wymiany walut - obecnie jeszcze nie używana, służyć ma przede wszystkim do raportów pomocniczych przy rozliczaniu podatków (w momencie przejścia z fiatów(innych niż złotówki) do krypto jako koszt liczy się wartość danej waluty fiducjarnej po jej koszcie tego dnia, a nie np po jakim się ją zakupiło). Zawiera następujące informacje: 
 - kursy wymiany (kolumna "exchange_rate")
 - waluta na którą się wymienia (kolumna "exchanged_currency_id")
 - waluta z której się wymienia (kolumna "base_currency_id")
 - data (kolumna "date") - dzienna data kursu wymiany

Relacje: 
 - Każdy dzienny kurs wymiany ma dokładnie jedną walutę(Currency) na którą i z której się wymienia
