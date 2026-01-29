# Planned to add to the package
- Classes to create generators for in order of priority
  - Database related classes:
    - factories
    - repositories
    
  - data transfer layer classes:
    - DTO's -> replaces array $data in some repository functions like store/update
    - Resource + ResourceCollection
    - Requests (validation rules and toDTO())

  - To be determined for now, end goal is to have all possible classes and tests