This API will accept a URL, type, sub-type & client.

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


### Response Params

| Param Name | Comment |
| ---------- | ------- |
| success | true if the article was created, false if there was a problem |

## Get Articles

## Get Articles
