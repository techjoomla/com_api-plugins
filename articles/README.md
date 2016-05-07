API to create and get content

## Create / Update Content

```http
POST /index.php?option=com_api&app=content&resource=articles&format=raw
```

### Request Params

| Param Name | Required | Comment |
| ---------- | -------- | ------- |
| id         | NO       |        | 
| title      | YES      |         |
| alias      | NO      | URL alias. Will be generated based on title if kept empty |
| introtext    | YES      |         |
| fulltext     | NO      |         |
| state    | NO      | 1 = Published (Default) / 0 = Unpublished / -1 = Archived |
| catid      | YES      |  Category ID |
| publish_up      | NO      | Defaults to current date/time |
| publish_down | NO | Defaults to 0000-00-00 i.e. never |
| language | NO | Will use the site's default language if none provided. |


### Response Params

| Param Name | Comment |
| ---------- | ------- |
| success | true if the article was created, false if there was a problem |
| message | Error mesage in case success is false |
| data.results | Array containing a single [Article Object](#article-object) in case of success. Empty array in case of failure. |

## Get Articles List
```http
GET /index.php?option=com_api&app=content&resource=articles&format=raw
```
### Request Params

| Param Name | Required | Comment |
| ---------- | -------- | ------- |
| limit         | NO       | Defaults to 20        | 
| limitstart      | NO      | Defaults to 0        |
| filters | NO | Key value pairs of values to filter on |
| search | NO | search key for searching article titles |
| fields         | NO       | Defaults to id, title, created, state, created_by, catid | 


### Response Params

| Param Name | Comment |
| ---------- | ------- |
| success | true if the article was created, false if there was a problem |
| message | Error mesage in case success is false |
| data.results | Array of [Article Objects](#article-object) in case of success. Empty array in case of failure. |


## Get Articles
```http
GET /index.php?option=com_api&app=content&resource=articles&format=raw&id=:id
```

### Request Params

| Param Name | Required | Comment |
| ---------- | -------- | ------- |
| fields         | NO       | Defaults to id, title, created, state, created_by, catid | 


### Response Params

| Param Name | Comment |
| ---------- | ------- |
| success | true if the article was created, false if there was a problem |
| message | Error mesage in case success is false |
| data.results | Array containing a single [Article Object](#article-object) in case of success. Empty array in case of failure. |


## Article Object
```json
{
  "id" : "",
  "title" : "",
  "alias" : "",
  "introtext" : "",
  "fulltext" : "",
  "catid" : {
    "id" : "",
    "title" : ""
  }
  "state" : "",
  "created" : "",
  "modified" : "",
  "publish_up" : "",
  "publish_down" : "",
  "images" : "",
  "access" : "",
  "featured" : "",
  "language" : "",
  "created_by": {
    "id" : "",
    "name" : "",
    "avatar" : ""
  }
}
```