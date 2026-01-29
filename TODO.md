# Planned to add to the package
- create a command for laravel
  - should prompt user what database connection to use (from config.database)
  - should prompt user if they want to view (select option) before generation
    - Option A: All tables + colums + relationships it found in the connected database
    - Option B: All tables + colums it found in the connected database
    - Option C: Only Tables it found in the connected database
    - Option D: Nothing
  - should prompt user what types of classes to generate using checkboxes or smt if possible
  - should prompt user if they want to see a file tree of what will be generated beforehand (Y/N)
  - (conditional) Show file tree if user has selected they want it 
  - should prompt user for safety that they understand files will be overwriten if present no matter what
  - should prompt user if they have made a backup or commited all their code just in case
  - should prompt user that this is the final warning and if they would like to proceed with the gneeration
  - (conditional if tests are selected in what classes to generate) should prompt user JK that wasn't the final warning we got a question before we generate: Want to run generated tests and show result after generation?
  - Run generation 

- Classes to create generators for in order of priority
  - Database related classes:
    - factories
    - repositories
    
  - data transfer layer classes:
    - DTO's -> replaces array $data in some repository functions like store/update
    - Resource + ResourceCollection
    - Requests (validation rules and toDTO())

  - To be determined for now, end goal is to have all possible classes and tests