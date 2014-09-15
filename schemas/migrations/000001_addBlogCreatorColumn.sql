ALTER TABLE blog_post ADD creatorId INT NOT NULL ;
UPDATE blog_post SET creatorId=(SELECT MIN(ID) from users) WHERE creatorId <1;
