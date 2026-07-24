# description
- an app to list items for borrow from neighbours

# interfaces
- login
- sign-in
- change pass
- logout
- listing of items having name, description, status, pictures from several users in the same group
- listing of loged in user items, with possibility to add, or change things for them


# architecture
- domain driven design application
- structure is a symfony classic, and domain/ and application/ folders
- use decoupling (ex. dependency inversion) to separate domain from application and from the rest(infrastructure)
- domain classes must not use application classes, and application classes should not use infrastructure classes
- CQS is required (except from Controllers that can return values even if they are POST PUT etc)
- except domain entities, all other objects must be immutable

# infrastructure
- symfony framework
  - logger
  - translation
  - validation
  - auth
  - ux

# Important
- do not touch folder src/Domain or src/Application unless stated by user, if any changes need to be done there, stop the agent execution
