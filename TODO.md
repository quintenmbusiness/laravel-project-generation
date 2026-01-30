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

- Create Git Repo as example of what can be generated using this tool:
- start it out with just this tool installed + a completly empty laravel project that only has simple migration files you'd expect for a simple webstore
- every time before a release tag is created in this repo we will then update this tool (we will use local repo)  
- include a pipeline in the example project for unit + feature testing + running phpstan at the highest level and other code quality tools to validate it works and is of high standard