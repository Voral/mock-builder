# 0.7.0 (2025-05-02)

### New features
- split multiple classes from a single file into separate files
- add conditional logging for global namespace classes
- add support for mocking global functions via MockFunctions class
- add support for tracking calling objects in mocked methods
- add auto-registration for mocked methods to handle undefined calls

### Fixes
- prevent duplicate processing of files during graph traversal
- resolve leading slash issue in class names by handling imports and namespaces
- replace "resource" return type with "mixed" to resolve PHP warnings

### Refactoring
- simplify mock configuration by removing explicit hash handling and supporting default parameters

# 0.6.0 (2025-04-29)

### New features
- implement inheritance of return types for methods
- add support for traits in dependency graph

### Fixes
- process files in topological order using Graph traversal

### Refactoring
- add EntityData class to represent class/interface information

# 0.5.2 (2025-04-29)

### Fixes
- handle `null` as `void` in return type processing

# 0.5.1 (2025-04-29)

### Fixes
- correct handling of array types in return type processing

# 0.5.0 (2025-04-28)

### New features
- add support for mocking echo outputs in MockTools
- remove mandatory namespace requirement for processed classes

### Documentation
- add guide for mocking global functions

# 0.4.0 (2025-04-28)

### New features
- add support for explicit method return types via config

### Fixes
- improve class name filtering in Builder. Method to support fully qualified class names.


# 0.3.0 (2025-04-28)

### New features
- add support for customizable base namespace and autonomous trait handling
- add dependency graph for accurate class hierarchy analysis

### Fixes
- correct handling of mixed type in return types

### Refactoring
- introduce Config class and streamline configuration handling
- introduce ModuleVisitor for centralized skip logic
- modularize AST processing with Visitor pattern and add PHP version support

# 0.2.0 (2025-04-24)

### New features
- add support for processing traits in mock generation

### Fixes
- preserve abstract methods without body during mock generation

### Other changes
- exclude .gitattributes from packaged releases
- exclude .vs-version-increment.php from packaged releases

# 0.1.0 (2025-04-24)

- Add core functionality for mock builder
